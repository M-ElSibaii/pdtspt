<?php

namespace App\Http\Controllers;

use App\Models\loins;
use Illuminate\Http\Request;
use App\Models\productdatatemplates;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\groupofproperties;
use App\Models\properties;
use App\Models\propertiesdatadictionaries;
use App\Models\Projects;
use App\Models\Milestones;
use App\Models\Actors;
use App\Models\Purposes;
use App\Models\Objects;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Response;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\LoinExport;
use App\Exports\ProjectLoinsExport;



class LoinsController extends Controller
{

    private function fetchIfcClasses()
    {
        $ifcClasses = [];

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'User-Agent' => 'pdtspt/1.0',
            ])->get('https://api.bsdd.buildingsmart.org/api/Dictionary/v1/Classes', [
                'Uri' => 'https://identifier.buildingsmart.org/uri/buildingsmart/ifc/4.3',
                'ClassType' => 'Class',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['classes'])) {
                    foreach ($data['classes'] as $class) {
                        $ifcClasses[] = [
                            'id' => $class['code'] ?? $class['id'],
                            'name' => $class['code'],
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error("Error fetching IFC classes: " . $e->getMessage());
        }

        return $ifcClasses;
    }

    private function fetchIfcProperties($ifcClass)
    {
        // Initialize an empty array for IFC properties
        $ifcProperties = [];

        try {
            // Make a GET request to fetch properties for the selected IFC element
            if ($ifcClass) {
                $response = Http::withHeaders([
                    'Accept' => 'application/json',
                    'User-Agent' => 'pdtspt/1.0',
                ])->get('https://api.bsdd.buildingsmart.org/api/Class/v1', [
                    'Uri' => 'https://identifier.buildingsmart.org/uri/buildingsmart/ifc/4.3/class/' . $ifcClass,
                    'IncludeClassProperties' => 'true'
                ]);

                // Log the response status and headers
                Log::info('Response Status: ' . $response->status());
                Log::info('Response Headers: ', $response->headers());

                if ($response->successful()) {
                    $data = $response->json();

                    // Extract properties and Property Sets if available
                    if (isset($data['classProperties'])) {
                        foreach ($data['classProperties'] as $property) {
                            $ifcProperties[] = [
                                'propertyName' => $property['propertyCode'] ?? 'N/A',
                                'propertySet' => $property['propertySet'] ?? 'N/A',
                                'propertyDescription' => $property['definition'] ?? 'N/A'
                            ];
                        }
                    }

                    Log::info("Fetched IFC Properties: ", $ifcProperties);
                } else {
                    Log::error("Failed to fetch IFC properties: " . $response->body());
                }
            }
        } catch (\Exception $e) {
            Log::error("Error fetching IFC properties: " . $e->getMessage());
        }

        return $ifcProperties;
    }

    //projects loin functions
    public function projectsindex()
    {
        // Get the authenticated user's ID
        $userId = auth()->id();

        // Retrieve only the projects belonging to the authenticated user and include the count of LOINs
        $projects = Projects::withCount('loins')
            ->where('userId', $userId)
            ->get();

        return view('loinproject', compact('projects'));
    }


    public function projectsstore(Request $request)
    {

        // Define custom error messages
        $messages = [
            'projectName.unique' => 'O nome do projeto deve ser único para o usuário.',
            'projectName.required' => 'O nome do projeto é obrigatório.',
            'projectName.string' => 'O nome do projeto deve ser uma string.',
            'projectName.max' => 'O nome do projeto não pode exceder 255 caracteres.',
        ];
        // Validate the incoming request data
        $request->validate([
            'projectName' => 'required|string|max:255|unique:projects,projectName,NULL,id,userId,' . Auth::id(),
            'description' => 'nullable|string',
        ], $messages);
        $userId = auth()->id();
        // Create a new project
        Projects::create([
            'projectName' => $request->projectName,
            'description' => $request->description,
            'userId' => $userId,
        ]);

        // Redirect back with success message
        return redirect()->route('loinproject')->with('success', __('Projeto criado com sucesso.'));
    }

    public function destroyproject($id)
    {
        // Attempt to find the project by ID
        $project = Projects::findOrFail($id);

        // Delete the project
        $project->delete();

        // Redirect back to the project listing page
        return redirect()->route('loinproject')->with('success', 'Projeto excluído com sucesso');
    }



    public function showLoinsByProject($projectId)
    {
        $loins = Loins::where('userId', Auth::id())
            ->where('projectId', $projectId)
            ->get();
        $project = projects::where('Id', $projectId)->first();

        return view('loinViewProject', compact('loins', 'projectId', 'project'));
    }


    // loin attributes page functions

    public function loinattributes(Request $request, $project)
    {
        // Fetch project from database using the ID
        $project = Projects::find($project);
        // Fetch related milestones, actors, purposes, objects
        $milestones = Milestones::where('projectId', $project->id)->get();
        $actors = Actors::where('projectId', $project->id)->get();
        $purposes = Purposes::where('projectId', $project->id)->get();
        $objects = Objects::where('projectId', $project->id)->get();
        // Fetch IFC classes

        $ifcClasses = $this->fetchIfcClasses();
        // Group product data templates by GUID and get the latest versions

        $userId = auth()->id();
        $pdtsall = productdatatemplates::All();
        $pdtslatest = $pdtsall->groupBy('GUID')->map(function ($group) {
            return $group->sortByDesc(function ($item) {
                return sprintf('%d.%010d.%010d', $item->editionNumber, $item->versionNumber, $item->revisionNumber);
            })->first();
        });
        // Prepare data for the view

        $pdts = $pdtslatest->values(); // Convert to collection

        return view('loinattributes', compact('project', 'milestones', 'actors', 'purposes', 'objects', 'ifcClasses', 'pdts'));
    }

    public function loinattributesstore(Request $request, $project)
    {
        // Filter out empty milestones, actors, purposes, and objects
        $milestones = array_filter($request->input('milestones', []), fn($value) => !is_null($value) && $value !== '');
        $actors = array_filter($request->input('actors', []), fn($value) => !is_null($value) && $value !== '');
        $purposes = array_filter($request->input('purposes', []), fn($value) => !is_null($value) && $value !== '');
        $objects = array_filter($request->input('objects', []), fn($value) => !is_null($value) && $value !== '');
        $ifcClasses = array_filter($request->input('ifcClasses', []), fn($value) => !is_null($value) && $value !== '');

        // Get the project ID from the request
        $project = Projects::find($project);
        $projectId = $project->id;
        foreach ($milestones as $milestone) {
            Milestones::create(['projectId' => $projectId, 'milestone' => $milestone]);
        }

        foreach ($actors as $actor) {
            Actors::create(['projectId' => $projectId, 'actor' => $actor]);
        }

        foreach ($purposes as $purpose) {
            Purposes::create(['projectId' => $projectId, 'purpose' => $purpose]);
        }

        foreach ($objects as $key => $object) {
            $ifcClass = $ifcClasses[$key] ?? null;
            if (!is_null($object) && $object !== '' && !is_null($ifcClass) && $ifcClass !== '') {

                Objects::create([
                    'projectId' => $projectId,
                    'object' => $object,
                    'ifcClass' => $request->ifcClasses[$key]  // Ensure the correct IFC class is linked with the object
                ]);
            }
        }

        return redirect()->route('loinattributes', ['project' => $projectId])->with('success', 'Attributes stored successfully.');
    }

    public function destroyattribute($project, $type, $id)
    {

        $model = $this->getModelFromType($type);
        $model::find($id)->delete();
        return redirect()->route('loinattributes', ['project' => $project])->with('success', 'Attributes deleted successfully.');
    }

    private function getModelFromType($type)
    {
        switch ($type) {
            case 'milestone':
                return Milestones::class;
            case 'actor':
                return Actors::class;
            case 'purpose':
                return Purposes::class;
            case 'object':
                return Objects::class;
        }
    }

    public function createLoin($project)
    {
        $userId = auth()->id(); // Get the current authenticated user's ID

        // Fetch project attributes
        $project = Projects::find($project);
        $milestones = Milestones::where('projectId', $project->id)->get();
        $actors = Actors::where('projectId', $project->id)->get();
        $purposes = Purposes::where('projectId', $project->id)->get();
        $objects = Objects::where('projectId', $project->id)->get();

        // Fetch PDTs from database
        $pdtsall = productdatatemplates::All();
        $pdtslatest = $pdtsall->groupBy('GUID')->map(function ($group) {
            return $group->sortByDesc(function ($item) {
                return sprintf('%d.%010d.%010d', $item->editionNumber, $item->versionNumber, $item->revisionNumber);
            })->first();
        });
        $pdts = $pdtslatest->values();

        // Fetch LOIN entries submitted by the current user
        $loins = Loins::where('userId', $userId)->where('projectId', $project->id)->get();


        return view('loincreate1', compact('project', 'milestones', 'actors', 'purposes', 'objects', 'pdts', 'loins'));
    }


    public function createloin2(Request $request)
    {
        // Retrieve selected options from the form
        $milestone = Milestones::find($request->milestone);
        $actorRequiring = Actors::find($request->actor_requiring);
        $actorProviding = Actors::find($request->actor_providing);
        $purpose = Purposes::find($request->purpose);
        $object = Objects::find($request->object);
        $ifcClass = $object->ifcClass;
        $pdt = productdatatemplates::find($request->pdt);
        $projectId = $request->projectId;
        $project = projects::where("id", $projectId)->first();
        $projectName = $project->projectName;


        $userId = auth()->id(); // Get the current authenticated user's ID


        // Fetch LOIN entries submitted by the current user
        $loins = Loins::where('userId', $userId)->where('projectId', $projectId)->get();

        // fetch ifc properties
        $ifcProperties = $this->fetchIfcProperties($ifcClass);

        // Initialize variables for PDT and its related data
        $pdts = null;
        $gops = collect(); // Initialize as an empty collection
        $properties = collect(); // Initialize as an empty collection

        // Check if a PDT is selected
        if ($request->has('pdt') && !empty($request->pdt)) {
            $pdt = productdatatemplates::find($request->pdt);
            if ($pdt) {
                $pdts = productdatatemplates::where('Id', $pdt->Id)->first();
                $gops = groupofproperties::where('pdtId', $pdt->Id)->get();
                $properties = properties::where('pdtID', $pdt->Id)->get();
            }
        }

        // Fetch  properties from dd
        $propertiesindd = propertiesdatadictionaries::all();

        // Fetch Master Data Template properties
        $MasterDataTemplate = productdatatemplates::where('pdtNameEn', 'Master')->latest()->first();
        $Mastergops = groupofproperties::where('pdtId', ($MasterDataTemplate->Id))->get();
        $MasterProperties = properties::where('pdtId', ($MasterDataTemplate->Id))->get();



        // Return the view with the IFC properties and other necessary data (remove name, remove classification code.)
        return view('loincreate2', [
            'projectId' =>  $projectId,
            'nomeProjeto' =>  $projectName,
            'ifcClass' => $object->ifcClass,
            'objeto' => $object->object,
            'proposito' => $purpose->purpose,
            'phase' => $milestone->milestone,
            'atorRequerente' => $actorRequiring->actor,
            'atorFornecedor' => $actorProviding->actor,
            'codigoClassificacao' => "code",
            'sistemaClassificacao' => "system",
            'tabelaClassificacao' => "table",
            'ifcProperties' => $ifcProperties,
            'pdts' => $pdts,
            'gops' => $gops,
            'properties' => $properties,
            'propertiesindd' => $propertiesindd,
            'MasterDataTemplate' => $MasterDataTemplate,
            'Mastergops' => $Mastergops,
            'MasterProperties' => $MasterProperties,
            'loins' => $loins,
        ]);
    }



    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function createLoin2store(Request $request)
    {
        $ifcClass = $request->input('ifcClass');
        $ifcClassName = $request->input('ifcClassName');
        $ifcClassDescription = $request->input('ifcClassDescription');
        $ifcClassPredefinedType = $request->input('ifcClassPredefinedType');
        $materialName = $request->input('materialName');
        $nomeProjeto = $request->input('nomeProjeto');
        $projectId = $request->input('projectId');
        $project = projects::where('Id', $projectId)->first();
        $objeto = $request->input('objeto');
        $pdtName = $request->input('pdtName');
        $atorRequerente = $request->input('atorRequerente');
        $atorFornecedor = $request->input('atorFornecedor');
        $phase = $request->input('phase');
        $proposito = $request->input('proposito');
        $sistemaClassificacao = trim($request->input('sistemaClassificacao')) ?: 'SECCLASS';
        $tabelaClassificacao = trim($request->input('tabelaClassificacao')) ?: 'Produtos';
        $userId = $request->input('userId');

        $validatedData = $request->validate([
            'ifcClass' => 'required|string|max:255',
            'ifcClassName' => 'required|string|max:255',
            'ifcClassDescription' => 'required|string|max:255',
            'ifcClassPredefinedType' => 'required|string|max:255',
            'materialName' => 'required|string|max:255',
            'nomeProjeto' => 'required|string|max:255',
            'projectId' => 'required|integer',
            'atorRequerente' => 'required|string|max:255',
            'atorFornecedor' => 'required|string|max:255',
            'phase' => 'required|string|max:255',
            'proposito' => 'required|string|max:255',
            'detalhe' => 'nullable|array',
            'dimensao' => 'nullable|string|max:255',
            'localizacao' => 'nullable|string|max:255',
            'aparencia' => 'nullable|string|max:255',
            'comportamentoParametrico' => 'nullable|string|max:255',
            'documents' =>  'nullable|array',
            'classificationCode' => 'nullable|string|max:255',
            'manual_properties' => 'nullable|string',
            'selected_ifc_properties' => 'nullable|array',
            'selected_pdt_properties' => 'nullable|array',
            'selected_master_pdt_properties' => 'nullable|array',
        ]);


        // Retrieve the 'detalhe' input and ensure it's an array
        $detalheArray = $request->input('detalhe', []);

        // If 'detalhe' is not an array, provide a default empty array
        if (!is_array($detalheArray)) {
            $detalheArray = [];
        }

        // Combine selected options into a single string separated by a semicolon
        $detalhe = implode(' - ', $detalheArray);

        $documents = [];

        // Decode documents input
        $documentsJson = $request->input('documents', '[]');
        $documents = json_decode($documentsJson, true);

        // Ensure $documents is an array
        if (!is_array($documents)) {
            $documents = [];
        }

        // Check if documents are empty, if so, set to "Não requerido"
        if (empty($documents)) {
            $documentation = 'Não requerido'; // Set documentation to a string
        } else {
            // Prepare an array for valid documents
            $validDocuments = [];

            // Iterate over manual properties
            foreach ($documents as $document) {
                // Ensure each manualProperty is an array with the expected keys
                if (is_array($document) && isset($document['document']) && isset($document['format'])) {
                    $validDocuments[] = [
                        'document' => $document['document'],
                        'format' => $document['format'],
                    ];
                }
            }
        }

        $alphanumericInfo = [];

        // Decode manual_properties input
        $manualPropertiesJson = $request->input('manual_properties', '[]');
        $manualProperties = json_decode($manualPropertiesJson, true);

        // Ensure $manualProperties is an array
        if (!is_array($manualProperties)) {
            $manualProperties = [];
        }
        // Iterate over manual properties
        foreach ($manualProperties as $manualProperty) {
            // Ensure each manualProperty is an array with the expected keys
            if (is_array($manualProperty) && isset($manualProperty['property']) && isset($manualProperty['group'])) {
                $alphanumericInfo[] = [
                    'property' => $manualProperty['property'],
                    'group' => $manualProperty['group'],
                    'source' => 'Manual',
                ];
            }
        }

        // Add IFC properties
        $ifcProperties = $request->input('selected_ifc_properties', []);
        foreach ($ifcProperties as $property) {
            list($propertyName, $propertySet) = explode(',', $property);
            $alphanumericInfo[] = [
                'property' => trim($propertyName),
                'group' => trim($propertySet),
                'source' => 'IFC',
            ];
        }

        // Add PDT properties
        $pdtProperties = $request->input('selected_pdt_properties', []);
        foreach ($pdtProperties as $property) {
            list($propertyName, $groupOfPropertyName) = explode(',', $property);
            $alphanumericInfo[] = [
                'property' => trim($propertyName),
                'group' => trim($groupOfPropertyName),
                'source' => $pdtName . ' Data Template',
            ];
        }

        // Add Master PDT properties
        $masterPdtProperties = $request->input('selected_master_pdt_properties', []);
        foreach ($masterPdtProperties as $property) {
            list($propertyName, $groupOfPropertyName) = explode(',', $property);
            $alphanumericInfo[] = [
                'property' => trim($propertyName),
                'group' => trim($groupOfPropertyName),
                'source' => 'Master Data Template',
            ];
        }

        // Create a new LOIN record
        $loin = Loins::create([
            'userId' => $userId,
            'projectId' => $projectId,
            'projectName' => $nomeProjeto,
            'objectName' => $objeto,
            'actorProviding' => $atorFornecedor,
            'actorRequesting' => $atorRequerente,
            'pdtName' => $pdtName,
            'ifcClass' => $ifcClass,
            'ifcClassName' => $ifcClassName,
            'ifcClassDescription' => $ifcClassDescription,
            'ifcClassPredefinedType' => $ifcClassPredefinedType,
            'materialName' => $materialName,
            'milestone' => $phase,
            'purpose' => $proposito,
            'detail' => $detalhe,
            'dimension' => $request->input('dimensao'),
            'location' => $request->input('localizacao'),
            'appearance' => $request->input('aparencia'),
            'parametricBehaviour' => $request->input('comportamentoParametrico'),
            'documentation' => $documentation,
            'classificationSystem' => $request->input('sistemaClassificacao'),
            'classificationTable' => $request->input('tabelaClassificacao'),
            'classificationCode' => $request->input('codigoClassificacao'),
            'properties' => json_encode($alphanumericInfo), // Store properties as JSON
        ]);

        // Fetch project attributes
        $milestones = Milestones::where('projectId', $project->id)->get();
        $actors = Actors::where('projectId', $project->id)->get();
        $purposes = Purposes::where('projectId', $project->id)->get();
        $objects = Objects::where('projectId', $project->id)->get();

        // Fetch PDTs from database
        $pdtsall = productdatatemplates::All();
        $pdtslatest = $pdtsall->groupBy('GUID')->map(function ($group) {
            return $group->sortByDesc(function ($item) {
                return sprintf('%d.%010d.%010d', $item->editionNumber, $item->versionNumber, $item->revisionNumber);
            })->first();
        });
        $pdts = $pdtslatest->values();

        return redirect()->route('loincreate1', compact('project', 'milestones', 'actors', 'purposes', 'objects', 'pdts'))->with('success', 'LOIN saved successfully.');
    }

    // Method to fetch and display a single LOIN instance
    public function loinInstance($loinId)
    {
        $loindata = Loins::findOrFail($loinId);
        return view('loinView', compact('loindata'));
    }

    // Method to delete a LOIN instance
    public function destroyLoin($loinId)
    {
        $loindata = Loins::findOrFail($loinId);
        $loindata->delete();

        return redirect('loin')->with('success', 'LOIN deleted successfully');
    }

    // Function to download JSON file
    public function downloadJSON($id)
    {
        $loin = Loins::findOrFail($id);

        $jsonData = [
            'Nome de Projeto' => $loin->projectName,
            'Objeto' => $loin->objectName,
            'IFC Class' => $loin->ifcClass,
            'PDT Nome' => $loin->pdtName,
            'Ator Fornecedor' => $loin->actorProviding,
            'Ator Requerente' => $loin->actorRequesting,
            'Fase de Projeto' => $loin->milestone,
            'Propósito' => $loin->purpose,
            'Sistema de Classificação' => $loin->classificationSystem,
            'Tabela de Classificação' => $loin->classificationTable,
            'Código de Classificação' => $loin->classificationCode,
            'Documentação' => $loin->documentation,
            'Geometrical Data' => [
                'Detalhe' => $loin->detail,
                'Dimensão' => $loin->dimension,
                'Localização' => $loin->location,
                'Aparência' => $loin->appearance,
                'Comportamento Paramétrico' => $loin->parametricBehaviour,
            ],
            'Alphanumerical Data' => [
                'IFC class name' => $loin->ifcClassName,
                'IFC class description' => $loin->ifcClassDescription,
                'IFC class PredefinedType' => $loin->ifcClassPredefinedType,
                'Propriedades' => json_decode($loin->properties, true)
            ]
        ];

        return Response::make(json_encode($jsonData, JSON_PRETTY_PRINT), 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="loin_' . $loin->objectName . '.json"',
        ]);
    }

    // Function to download Excel file
    public function downloadExcel($id, $objectName)
    {
        return Excel::download(new LoinExport($id), 'loin_' . $objectName . '.xlsx');
    }

    // Excel export for all LOINs under a project
    public function exportProjectLoinsExcel($project)
    {
        return Excel::download(new ProjectLoinsExport($project->id), 'loins_project_' . $project->projectName . '.xlsx');
    }

    // JSON export for all LOINs under a project
    public function exportProjectLoinsJson($project)
    {
        $loins = Loins::where('projectId', $project->id)->get();

        $jsonData = $loins->map(function ($loin) {
            return [
                'Nome de Projeto' => $loin->projectName,
                'Objeto' => $loin->objectName,
                'IFC Class' => $loin->ifcClass,
                'PDT Nome' => $loin->pdtName,
                'Ator Fornecedor' => $loin->actorProviding,
                'Ator Requerente' => $loin->actorRequesting,
                'Fase de Projeto' => $loin->milestone,
                'Propósito' => $loin->purpose,
                'Sistema de Classificação' => $loin->classificationSystem,
                'Tabela de Classificação' => $loin->classificationTable,
                'Código de Classificação' => $loin->classificationCode,
                'Documentação' => $loin->documentation,
                'Geometrical Data' => [
                    'Detalhe' => $loin->detail,
                    'Dimensão' => $loin->dimension,
                    'Localização' => $loin->location,
                    'Aparência' => $loin->appearance,
                    'Comportamento Paramétrico' => $loin->parametricBehaviour,
                ],
                'Alphanumerical Data' => [
                    'IFC class name' => $loin->ifcClassName,
                    'IFC class description' => $loin->ifcClassDescription,
                    'IFC class PredefinedType' => $loin->ifcClassPredefinedType,
                    'Propriedades' => json_decode($loin->properties, true)
                ]
            ];
        });

        // Provide a download response
        return response()->streamDownload(function () use ($jsonData) {
            echo json_encode($jsonData, JSON_PRETTY_PRINT);
        }, 'loins_project_' . $project->projectName . '.json', [
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }



    /**
     * Display the specified resource.
     *
     * @param  \App\Models\loins  $loins
     * @return \Illuminate\Http\Response
     */
    public function show(loins $loins)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\loins  $loins
     * @return \Illuminate\Http\Response
     */
    public function edit(loins $loins)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\loins  $loins
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, loins $loins)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\loins  $loins
     * @return \Illuminate\Http\Response
     */
    public function destroy(loins $loins)
    {
        //
    }
}
