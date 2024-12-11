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
use App\Models\Classification;
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
        $offset = 0;
        $limit = 1000; // API's maximum limit

        try {
            do {
                $response = Http::withHeaders([
                    'Accept' => 'application/json',
                    'User-Agent' => 'pdtspt/1.0',
                ])->get('https://api.bsdd.buildingsmart.org/api/Dictionary/v1/Classes', [
                    'Uri' => 'https://identifier.buildingsmart.org/uri/buildingsmart/ifc/4.3',
                    'ClassType' => 'Class',
                    'Offset' => $offset,
                    'Limit' => $limit,
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    if (isset($data['classes']) && is_array($data['classes'])) {
                        foreach ($data['classes'] as $class) {
                            $ifcClasses[] = [
                                'id' => $class['code'] ?? $class['id'],
                                'name' => $class['code'] ?? $class['id'], // Ensure 'code' is used when available
                            ];
                        }
                    }
                    // Increase offset for next batch
                    $offset += $limit;
                } else {
                    Log::error("Error fetching IFC classes: API response unsuccessful. Status: " . $response->status());
                    break;
                }
            } while (!empty($data['classes']) && count($data['classes']) === $limit); // Continue fetching until fewer than limit

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

    public function fetchIfcPropertiesAjax($ifcClass)
    {
        $ifcProperties = $this->fetchIfcProperties($ifcClass);

        return response()->json($ifcProperties);
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

        // Validate the request
        $request->validate([
            'projectName' => 'required|string|max:255|unique:projects,projectName,NULL,id,userId,' . Auth::id(),
            'description' => 'nullable|string',
            'classificationSystem' => 'nullable|string|max:255',
            'customClassificationSystem' => 'nullable|string|max:255',
        ], $messages);

        $userId = auth()->id();

        // Create the project
        $project = Projects::create([
            'projectName' => $request->projectName,
            'description' => $request->description,
            'userId' => $userId,
        ]);

        // Save classification only if provided
        if ($request->classificationSystem || $request->customClassificationSystem) {
            Classification::create([
                'classification_system' => $request->customClassificationSystem ?: $request->classificationSystem,
                'project_id' => $project->id,
            ]);
        }

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

        // Create entries only for non-empty attributes
        if (!empty($milestones)) {
            foreach ($milestones as $milestone) {
                Milestones::create(['projectId' => $projectId, 'milestone' => $milestone]);
            }
        }

        if (!empty($actors)) {
            foreach ($actors as $actor) {
                Actors::create(['projectId' => $projectId, 'actor' => $actor]);
            }
        }

        if (!empty($purposes)) {
            foreach ($purposes as $purpose) {
                Purposes::create(['projectId' => $projectId, 'purpose' => $purpose]);
            }
        }

        if (!empty($objects)) {
            foreach ($objects as $key => $object) {
                // Get the corresponding ifcClass or set to 'ifcElement' if not defined
                $ifcClass = $ifcClasses[$key] ?? 'ifcElement'; // Default value if no class is specified

                if (!is_null($object) && $object !== '') {
                    Objects::create([
                        'projectId' => $projectId,
                        'object' => $object,
                        'ifcClass' => $ifcClass // Use the default ifcClass if not defined
                    ]);
                }
            }
        }

        return redirect()->route('loinattributes', ['project' => $projectId])->with('success', 'pré-requisitos guardados com sucesso.');
    }

    public function deleteAttribute(Request $request, $projectId)
    {
        $attributeType = $request->input('attributeType');
        $id = $request->input('id');

        // Determine the model to use based on the attribute type
        switch ($attributeType) {
            case 'milestone':
                $attribute = Milestones::where('projectId', $projectId)->find($id);
                break;
            case 'actor':
                $attribute = Actors::where('projectId', $projectId)->find($id);
                break;
            case 'purpose':
                $attribute = Purposes::where('projectId', $projectId)->find($id);
                break;
            case 'object':
                $attribute = Objects::where('projectId', $projectId)->find($id);
                break;
            default:
                return response()->json(['error' => 'Invalid attribute type.'], 400);
        }

        // Check if the attribute exists and delete it
        if ($attribute) {
            $attribute->delete();
            return response()->json(['success' => 'Attribute deleted successfully.']);
        } else {
            return response()->json(['error' => 'Attribute not found.'], 404);
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

    public function searchProperties(Request $request)
    {
        $query = $request->query('query');

        // Retrieve all properties from the session
        $allProperties = session('allProperties');

        if (!$allProperties) {
            return response()->json([]); // Return an empty array if no properties found
        }

        // Filter properties based on the query
        $filteredProperties = collect($allProperties)->filter(function ($property) use ($query) {
            return stripos($property['name'], $query) !== false; // Case-insensitive search
        })->values(); // Reset the keys

        return response()->json($filteredProperties);
    }

    public function fetchProperties($pdtId)
    {
        try {
            $pdt = productdatatemplates::find($pdtId);
            if (!$pdt) {
                return response()->json(['success' => false, 'message' => 'PDT not found.']);
            }

            $gops = groupofproperties::where('pdtId', $pdtId)->get();
            $properties = properties::where('pdtID', $pdtId)->get();
            $propertiesindd = propertiesdatadictionaries::all();

            // Group properties by their respective groups
            $groupedProperties = [];
            foreach ($gops as $gop) {
                $grouped = $properties->where('gopID', $gop->Id)->map(function ($property) use ($propertiesindd, $gop) {
                    $propertyDetails = $propertiesindd->firstWhere('Id', $property->propertyId);
                    return [
                        'name' => $propertyDetails->nameEn ?? 'N/A',
                        'description' => $propertyDetails->definitionEn ?? 'N/A',
                        'group' => $gop->gopNameEn ?? 'N/A'
                    ];
                });
                $groupedProperties[$gop->gopNameEn] = $grouped->toArray();
            }

            return response()->json(['success' => true, 'groupedProperties' => $groupedProperties]);
        } catch (\Exception $e) {
            Log::error("Error fetching PDT properties: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'An error occurred while fetching properties.']);
        }
    }



    public function createloin2(Request $request)
    {
        // Retrieve selected options from the form
        $milestone = Milestones::find($request->milestone);
        $actorRequiring = Actors::find($request->actor_requiring);
        $actorProviding = Actors::find($request->actor_providing);
        $purpose = Purposes::find($request->purpose);
        $projectId = $request->projectId;
        $project = projects::where("id", $projectId)->first();
        $projectName = $project->projectName;
        $objects = Objects::where('projectId', $projectId)->get();

        // Fetch IFC classes
        $ifcClasses = $this->fetchIfcClasses();

        $userId = auth()->id(); // Get the current authenticated user's ID


        // Fetch LOIN entries submitted by the current user
        $loins = Loins::where('userId', $userId)->where('projectId', $projectId)->get();

        // Fetch  properties from dd
        $propertiesindd = propertiesdatadictionaries::all();

        // Fetch Master Data Template properties
        $MasterDataTemplate = productdatatemplates::where('pdtNameEn', 'Master')->latest()->first();
        $Mastergops = groupofproperties::where('pdtId', ($MasterDataTemplate->Id))->get();
        $MasterProperties = properties::where('pdtId', ($MasterDataTemplate->Id))->get();


        // get classification information
        $classificationsystem = Classification::where('projectId', $projectId)->first();

        //fetch all pdts

        $pdtsall = productdatatemplates::All();
        $pdtslatest = $pdtsall->groupBy('GUID')->map(function ($group) {
            return $group->sortByDesc(function ($item) {
                return sprintf('%d.%010d.%010d', $item->editionNumber, $item->versionNumber, $item->revisionNumber);
            })->first();
        });
        // Prepare Master Properties for the search bar
        $masterPropertiesArray = $MasterProperties->map(function ($property) {
            // Fetch the name, description, and group separately
            $propertyName = propertiesdatadictionaries::where('Id', $property->propertyId)->value('nameEn');
            $propertyDescription = propertiesdatadictionaries::where('Id', $property->propertyId)->value('definitionEn');
            $groupName = groupofproperties::where('Id', $property->gopID)->value('gopNameEn');

            // Return standardized structure
            return [
                'name' => $propertyName ?? 'N/A',           // Ensure default if null
                'description' => $propertyDescription ?? 'N/A',
                'group' => $groupName ?? 'N/A'
            ];
        });

        // Prepare data for the view

        $pdtslatestversions = $pdtslatest->values(); // Convert to collection

        // Return the view with the IFC properties and other necessary data
        return view('loincreate2', [
            'projectId' =>  $projectId,
            'nomeProjeto' =>  $projectName,
            'objects' => $objects,
            'proposito' => $purpose->purpose,
            'phase' => $milestone->milestone,
            'atorRequerente' => $actorRequiring->actor,
            'atorFornecedor' => $actorProviding->actor,
            'sistemaClassificacao' => $classificationsystem->classificationSystem,
            'pdtslatestversions' => $pdtslatestversions,
            'propertiesindd' => $propertiesindd,
            'MasterDataTemplate' => $MasterDataTemplate,
            'Mastergops' => $Mastergops,
            'MasterProperties' => $MasterProperties,
            'masterPropertiesArray' => $masterPropertiesArray,
            'loins' => $loins,
            'ifcClasses' => $ifcClasses,
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
        $documentation = json_decode($loin->documentation, true);
        if (is_null($documentation)) {
            $documentation = $loin->documentation; // Set as raw string if not valid JSON
        }

        $jsonData = [
            'Nome de Projeto' => $loin->projectName,
            'Objeto' => $loin->objectName,
            'PDT Nome' => $loin->pdtName,
            'Ator Fornecedor' => $loin->actorProviding,
            'Ator Requerente' => $loin->actorRequesting,
            'Fase de Projeto' => $loin->milestone,
            'Propósito' => $loin->purpose,
            'Documentação' => $documentation,
            'Geometrical Data' => [
                'Detalhe' => $loin->detail,
                'Dimensão' => $loin->dimension,
                'Localização' => $loin->location,
                'Aparência' => $loin->appearance,
                'Comportamento Paramétrico' => $loin->parametricBehaviour,
            ],
            'Alphanumerical Data' => [
                'IFC Class' => $loin->ifcClass,
                'IFC class name' => $loin->ifcClassName,
                'IFC class description' => $loin->ifcClassDescription,
                'IFC class PredefinedType' => $loin->ifcClassPredefinedType,
                'Sistema de Classificação' => $loin->classificationSystem,
                'Tabela de Classificação' => $loin->classificationTable,
                'Código de Classificação' => $loin->classificationCode,
                'IfcMaterial Name' => $loin->materialName,
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
            // Decode the documentation for each LOIN
            $documentation = json_decode($loin->documentation, true);
            if (is_null($documentation)) {
                $documentation = $loin->documentation; // Set as raw string if not valid JSON
            }
            return [
                'Nome de Projeto' => $loin->projectName,
                'Objeto' => $loin->objectName,
                'PDT Nome' => $loin->pdtName,
                'Ator Fornecedor' => $loin->actorProviding,
                'Ator Requerente' => $loin->actorRequesting,
                'Fase de Projeto' => $loin->milestone,
                'Propósito' => $loin->purpose,
                'Documentação' => $documentation,
                'Geometrical Data' => [
                    'Detalhe' => $loin->detail,
                    'Dimensão' => $loin->dimension,
                    'Localização' => $loin->location,
                    'Aparência' => $loin->appearance,
                    'Comportamento Paramétrico' => $loin->parametricBehaviour,
                ],
                'Alphanumerical Data' => [
                    'IFC Class' => $loin->ifcClass,
                    'IFC class name' => $loin->ifcClassName,
                    'IFC class description' => $loin->ifcClassDescription,
                    'IFC class PredefinedType' => $loin->ifcClassPredefinedType,
                    'Sistema de Classificação' => $loin->classificationSystem,
                    'Tabela de Classificação' => $loin->classificationTable,
                    'Código de Classificação' => $loin->classificationCode,
                    'IfcMaterial Name' => $loin->materialName,
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
