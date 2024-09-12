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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Response;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\LoinExport;
use App\Exports\ProjectLoinsExport;



class LoinsController extends Controller
{

    public function dataforloin()
    {
        $userId = auth()->id(); // Get the current authenticated user's ID
        $pdtsall = productdatatemplates::All();
        $pdtslatest = $pdtsall->groupBy('GUID')->map(function ($group) {
            return $group->sortByDesc(function ($item) {
                return sprintf('%d.%010d.%010d', $item->editionNumber, $item->versionNumber, $item->revisionNumber);
            })->first();
        });

        $pdts = $pdtslatest->values(); // Convert to collection
        // Initialize $ifcClasses to an empty array
        $ifcClasses = [];

        try {
            // Make a GET request to the bSDD API endpoint
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'User-Agent' => 'pdtspt/1.0',
            ])->get('https://api.bsdd.buildingsmart.org/api/Dictionary/v1/Classes', [
                'Uri' => 'https://identifier.buildingsmart.org/uri/buildingsmart/ifc/4.3',
                'ClassType' => 'Class',
            ]);

            // Log the response status and headers
            Log::info('Response Status: ' . $response->status());
            Log::info('Response Headers: ', $response->headers());

            // Check if the request was successful
            if ($response->successful()) {
                // Decode the JSON response
                $data = $response->json();
                // Log the response body
                Log::info('Response Body: ', $data);

                // Extract class information from the response
                if (isset($data['classes'])) {
                    foreach ($data['classes'] as $class) {
                        $ifcClasses[] = [
                            'id' => $class['code'] ?? $class['id'], // Fallback to 'id' if 'code' is not set
                            'name' => $class['code'],
                        ];
                    }
                }

                // Log the extracted IFC classes
                Log::info("Fetched IFC Classes: ", $ifcClasses);
            } else {
                // Log the error response body
                Log::error("Failed to fetch IFC classes: " . $response->body());
            }
        } catch (\Exception $e) {
            // Log any exceptions that occur
            Log::error("Error fetching IFC classes: " . $e->getMessage());
        }

        // Get all loins grouped by projectName for the authenticated user
        $allUserLoins = Loins::where('userId', Auth::id())
            ->select('projectName', DB::raw('count(*) as loin_count'))
            ->groupBy('projectName')
            ->get();

        // Return the view with all necessary data
        return view('loin', compact('ifcClasses', 'pdts',  'allUserLoins'));
    }

    public function showLoinsByProject($projectName)
    {
        $loins = Loins::where('userId', Auth::id())
            ->where('projectName', $projectName)
            ->get();

        return view('loinViewProject', compact('loins', 'projectName'));
    }

    public function dataforloin1(Request $request)
    {
        //$details = $request->session()->get('details', []);


        $userId = auth()->id(); // Get the current authenticated user's ID


        // Retrieve data from the first page form submission
        $ifcElement = $request->input('ifcElement');
        $nomeProjeto = $request->input('nomeProjeto');
        $nomeObjeto = $request->input('nomeObjeto');
        $objectPDTId = $request->input('objectPDTId');
        $atorRequerente = $request->input('atorRequerente');
        $atorFornecedor = $request->input('atorFornecedor');
        $nome = $request->input('nome');
        $sistemaClassificacao = trim($request->input('sistemaClassificacao')) ?: 'SECCLASS';
        $tabelaClassificacao = trim($request->input('tabelaClassificacao')) ?: 'Produtos';
        $codigoClassificacao = $request->input('codigoClassificacao');


        // fetch LOINS

        // Fetch LOIN entries submitted by the current user
        $loins = Loins::where('userId', $userId)->where('projectName', $nomeProjeto)->get();

        // Fetch data from your models
        $pdts = productdatatemplates::where('Id', $objectPDTId)->first();
        $gops = groupofproperties::where('pdtId', $objectPDTId)->get();
        $properties = properties::where('pdtID', $objectPDTId)->get();
        $propertiesindd = propertiesdatadictionaries::all();

        // Fetch Master Data Template properties
        $MasterDataTemplate = productdatatemplates::where('pdtNameEn', 'Master')->latest()->first();
        $Mastergops = groupofproperties::where('pdtId', ($MasterDataTemplate->Id))->get();
        $MasterProperties = properties::where('pdtId', ($MasterDataTemplate->Id))->get();

        // Initialize an empty array for IFC properties
        $ifcProperties = [];

        try {
            // Make a GET request to fetch properties for the selected IFC element
            if ($ifcElement) {
                $response = Http::withHeaders([
                    'Accept' => 'application/json',
                    'User-Agent' => 'pdtspt/1.0',
                ])->get('https://api.bsdd.buildingsmart.org/api/Class/v1', [
                    'Uri' => 'https://identifier.buildingsmart.org/uri/buildingsmart/ifc/4.3/class/' . $ifcElement,
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
                                'propertySet' => $property['propertySet'] ?? 'N/A'
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

        // Return the view with the IFC properties and other necessary data
        return view('loin1', [
            'nomeProjeto' => $nomeProjeto,
            'ifcElement' => $ifcElement,
            'nomeObjeto' => $nomeObjeto,
            'atorRequerente' => $atorRequerente,
            'atorFornecedor' => $atorFornecedor,
            'nome' => $nome,
            'codigoClassificacao' => $codigoClassificacao,
            'sistemaClassificacao' => $sistemaClassificacao,
            'tabelaClassificacao' => $tabelaClassificacao,
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

    public function store(Request $request)
    {
        $ifcElement = $request->input('ifcElement');
        $nomeProjeto = $request->input('nomeProjeto');
        $nomeObjeto = $request->input('nomeObjeto');
        $objectPDTId = $request->input('objectPDTId');
        $atorRequerente = $request->input('atorRequerente');
        $atorFornecedor = $request->input('atorFornecedor');
        $nome = $request->input('nome');
        $sistemaClassificacao = trim($request->input('sistemaClassificacao')) ?: 'SECCLASS';
        $tabelaClassificacao = trim($request->input('tabelaClassificacao')) ?: 'Produtos';
        $codigoClassificacao = $request->input('codigoClassificacao');
        $userId = $request->input('userId');
        $pdts = productdatatemplates::where('Id', $objectPDTId)->first();

        // fetch LOINS

        // Fetch LOIN entries submitted by the current user
        $loins = Loins::where('userId', $userId)->where('projectName', $nomeProjeto)->get();

        // Fetch data from your models
        $pdts = productdatatemplates::where('Id', $objectPDTId)->first();
        $gops = groupofproperties::where('pdtId', $objectPDTId)->get();
        $properties = properties::where('pdtID', $objectPDTId)->get();
        $propertiesindd = propertiesdatadictionaries::all();

        // Fetch Master Data Template properties
        $MasterDataTemplate = productdatatemplates::where('pdtNameEn', 'Master')->latest()->first();
        $Mastergops = groupofproperties::where('pdtId', ($MasterDataTemplate->Id))->get();
        $MasterProperties = properties::where('pdtId', ($MasterDataTemplate->Id))->get();

        // Initialize an empty array for IFC properties
        $ifcPropertiesarray = [];

        try {
            // Make a GET request to fetch properties for the selected IFC element
            if ($ifcElement) {
                $response = Http::withHeaders([
                    'Accept' => 'application/json',
                    'User-Agent' => 'pdtspt/1.0',
                ])->get('https://api.bsdd.buildingsmart.org/api/Class/v1', [
                    'Uri' => 'https://identifier.buildingsmart.org/uri/buildingsmart/ifc/4.3/class/' . $ifcElement,
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
                            $ifcPropertiesarray[] = [
                                'propertyName' => $property['propertyCode'] ?? 'N/A',
                                'propertySet' => $property['propertySet'] ?? 'N/A'
                            ];
                        }
                    }

                    Log::info("Fetched IFC Properties: ", $ifcPropertiesarray);
                } else {
                    Log::error("Failed to fetch IFC properties: " . $response->body());
                }
            }
        } catch (\Exception $e) {
            Log::error("Error fetching IFC properties: " . $e->getMessage());
        }
        //ADD CLASSIFICATION
        // Validate the request

        $validatedData = $request->validate([

            'nomeProjeto' => 'required|string|max:255',
            'faseDeProjeto' => 'required|string|max:255',
            'nomeObjeto' => 'required|string|max:255',
            'nome' => 'nullable|string|max:255',
            'atorFornecedor' => 'required|string|max:255',
            'atorRequerente' => 'required|string|max:255',
            'pdtName' => 'nullable|string|max:255',
            'ifcElement' => 'required|string|max:255',
            'proposito' => 'nullable|string|max:255',
            'detalhe' => 'nullable|array',
            'dimensao' => 'nullable|string|max:255',
            'localizacao' => 'nullable|string|max:255',
            'aparencia' => 'nullable|string|max:255',
            'comportamentoParametrico' => 'nullable|string|max:255',
            'documentacao' => 'nullable|string|max:255',
            'classificationSystem' => 'nullable|string|max:255',
            'classificationTable' => 'nullable|string|max:255',
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

        // Check and set default value for 'documentacao'
        $documentacao = $request->input('documentacao');
        if (empty($documentacao)) {
            $documentacao = 'Não requerido';
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
                'source' => 'PDT',
            ];
        }

        // Add Master PDT properties
        $masterPdtProperties = $request->input('selected_master_pdt_properties', []);
        foreach ($masterPdtProperties as $property) {
            list($propertyName, $groupOfPropertyName) = explode(',', $property);
            $alphanumericInfo[] = [
                'property' => trim($propertyName),
                'group' => trim($groupOfPropertyName),
                'source' => 'MasterPDT',
            ];
        }

        // Create a new LOIN record
        $loin = Loins::create([
            'userId' => $userId,
            'projectName' => $request->input('nomeProjeto'),
            'objectName' => $request->input('nomeObjeto'),
            'name' => $request->input('nome'),
            'actorProviding' => $request->input('atorFornecedor'),
            'actorRequesting' => $request->input('atorRequerente'),
            'pdtName' => $request->input('pdtName'),
            'ifcElement' => $request->input('ifcElement'),
            'projectPhase' => $request->input('faseDeProjeto'),
            'purpose' => $request->input('proposito'),
            'detail' => $detalhe,
            'dimension' => $request->input('dimensao'),
            'location' => $request->input('localizacao'),
            'appearance' => $request->input('aparencia'),
            'parametricBehaviour' => $request->input('comportamentoParametrico'),
            'documentation' => $request->input('documentacao', 'Não requerido'),
            'classificationSystem' => $request->input('sistemaClassificacao'),
            'classificationTable' => $request->input('tabelaClassificacao'),
            'classificationCode' => $request->input('codigoClassificacao'),
            'properties' => json_encode($alphanumericInfo), // Store properties as JSON
        ]);

        return view('loin1', [
            'nomeProjeto' => $nomeProjeto,
            'ifcElement' => $ifcElement,
            'ifcProperties' => $ifcPropertiesarray,
            'nomeObjeto' => $nomeObjeto,
            'atorRequerente' => $atorRequerente,
            'atorFornecedor' => $atorFornecedor,
            'nome' => $nome,
            'pdts' => $pdts,
            'codigoClassificacao' => $codigoClassificacao,
            'sistemaClassificacao' => $sistemaClassificacao,
            'tabelaClassificacao' => $tabelaClassificacao,
            'gops' => $gops,
            'properties' => $properties,
            'propertiesindd' => $propertiesindd,
            'MasterDataTemplate' => $MasterDataTemplate,
            'Mastergops' => $Mastergops,
            'MasterProperties' => $MasterProperties,
            'loins' => $loins,

        ])->with('success', 'LOIN saved successfully.');
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
            'IFC Class' => $loin->ifcElement,
            'PDT Nome' => $loin->pdtName,
            'Ator Fornecedor' => $loin->actorProviding,
            'Ator Requerente' => $loin->actorRequesting,
            'Fase de Projeto' => $loin->projectPhase,
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
                'Nome' => $loin->name,
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
    public function exportProjectLoinsExcel($projectName)
    {
        return Excel::download(new ProjectLoinsExport($projectName), 'loins_project_' . $projectName . '.xlsx');
    }

    // JSON export for all LOINs under a project
    public function exportProjectLoinsJson($projectName)
    {
        $loins = Loins::where('projectName', $projectName)->get();

        $jsonData = $loins->map(function ($loin) {
            return [
                'Nome de Projeto' => $loin->projectName,
                'Objeto' => $loin->objectName,
                'IFC Class' => $loin->ifcElement,
                'PDT Nome' => $loin->pdtName,
                'Ator Fornecedor' => $loin->actorProviding,
                'Ator Requerente' => $loin->actorRequesting,
                'Fase de Projeto' => $loin->projectPhase,
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
                    'Nome' => $loin->name,
                    'Propriedades' => json_decode($loin->properties, true)
                ]
            ];
        });

        // Provide a download response
        return response()->streamDownload(function () use ($jsonData) {
            echo json_encode($jsonData, JSON_PRETTY_PRINT);
        }, 'loins_project_' . $projectName . '.json', [
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
