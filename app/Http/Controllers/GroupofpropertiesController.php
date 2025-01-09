<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\productdatatemplates;
use App\Models\groupofproperties;
use App\Models\properties;
use App\Models\depreciatedproperties;
use App\Models\comments;
use App\Models\Answers;
use App\Models\User;
use App\Models\propertiesdatadictionaries;
use App\Models\referencedocuments;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Mail\FeedbackMailAdmin;
use App\Mail\FeedbackMailUsers;
use App\Mail\SurveyMailAdmin;
use Illuminate\Support\Facades\Mail;

class GroupofpropertiesController extends Controller
{

    //function for pdtdownload page
    public function getGroupOfProperties($pdtID)
    {
        $pdt = productdatatemplates::where('Id', $pdtID)->first();
        $pdtGUID = $pdt->GUID;
        $pdts = productdatatemplates::where('GUID', $pdtGUID)
            ->orderBy('versionNumber', 'asc')
            ->orderBy('revisionNumber', 'asc')
            ->orderBy('editionNumber', 'asc')
            ->get();

        $latestPdt = $pdts->last();

        $masterpdt = productdatatemplates::where('GUID', '230d9954097541b793f2a1fddb8bd0ad')->latest()->first();
        $pdt_groups = groupofproperties::where('pdtId', $pdtID)->get();
        $master_groups = groupofproperties::where('pdtId', $masterpdt->Id)->get();

        $properties = properties::where('pdtID', $pdtID)->get();
        $properties_dict = propertiesdatadictionaries::all();
        $referenceDocument = referencedocuments::all();

        $joined_properties = properties::leftJoin('propertiesdatadictionaries', function ($join) {
            $join->on('properties.propertyId', '=', 'propertiesdatadictionaries.Id');
        })->select(
            'properties.descriptionEn',
            'properties.descriptionPt',
            'properties.GUID',
            'properties.Id',
            'properties.pdtID',
            'properties.propertyId',
            'propertiesdatadictionaries.versionNumber',
            'propertiesdatadictionaries.status',
            'propertiesdatadictionaries.relationToOtherDataDictionaries',
            'properties.gopID',
            'properties.referenceDocumentGUID',
            'propertiesdatadictionaries.units',
            'propertiesdatadictionaries.nameEn',
            'propertiesdatadictionaries.namePt',
            'propertiesdatadictionaries.nameEnSc',
            'propertiesdatadictionaries.namePtSc',
            'properties.visualRepresentation'
        )->get();

        $joined_properties->each(function ($property) use ($masterpdt) {
            $property->from_master = ($property->pdtID == $masterpdt->Id);
        });

        $merged_groups = $pdt_groups->merge($master_groups);
        $combined_groups = $merged_groups->groupBy('gopNamePt');

        $group_order = [
            'Dados de classificação',
            'Dados gerais',
            'Dados do fabricante',
            'Dados de desempenho',
            'Dados de especificação',
            'Dados geométricos'
        ];

        // Sort groups based on the specified order, placing unmatched ones after the specified ones
        $sorted_combined_groups = $combined_groups->sortBy(function ($_, $key) use ($group_order) {
            $index = array_search($key, $group_order);
            return $index === false ? count($group_order) : $index;
        });

        // Move 'Dados de gestão de instalações' and 'Dados de sustentabilidade' to the end
        $sorted_combined_groups = $sorted_combined_groups->sortBy(function ($_, $key) {
            if ($key === 'Dados de gestão de instalações') {
                return PHP_INT_MAX - 1; // Second to last position
            }
            if ($key === 'Dados de sustentabilidade') {
                return PHP_INT_MAX; // Last position
            }
            return 0; // Default ordering for others
        });

        return view('pdtsdownload', compact('sorted_combined_groups', 'joined_properties', 'properties_dict', 'pdt', 'referenceDocument', 'latestPdt'));
    }


    //function for survey page
    public function getCommentProperty($propID)
    {
        $comments = comments::with('user')->where('properties_Id', $propID)->get();

        return response()->json([
            'comments' => $comments
        ]);
    }
    public function getGroupOfProperties2($pdtID)
    {

        $pdt = productdatatemplates::where('Id', $pdtID)->first();
        $pdtGUID = $pdt->GUID;
        $pdts = productdatatemplates::where('GUID', $pdtGUID)
            ->orderBy('versionNumber', 'asc')
            ->orderBy('revisionNumber', 'asc')
            ->orderBy('editionNumber', 'asc')
            ->get();

        $pdtCount = $pdts->count();
        $latestPdt = $pdts[$pdtCount - 1];

        // Fetch the groups for PDT properties and Master properties
        $masterpdt = productdatatemplates::where('GUID', '230d9954097541b793f2a1fddb8bd0ad')->latest()->first();
        $pdt_groups = groupofproperties::where('pdtId', $pdtID)->get();
        $master_groups = groupofproperties::where('pdtId', $masterpdt->Id)->get();

        // Get all properties linked to the PDT
        $properties = properties::where('pdtID', $pdtID)->get();
        $properties_dict = propertiesdatadictionaries::all();
        $referenceDocument = referencedocuments::all();

        // Join properties and propertiesdatadictionaries
        $joined_properties = properties::leftJoin('propertiesdatadictionaries', function ($join) {
            $join->on('properties.propertyId', '=', 'propertiesdatadictionaries.Id');
        })->select(
            'properties.descriptionEn',
            'properties.descriptionPt',
            'properties.GUID',
            'properties.Id',
            'properties.pdtID',
            'properties.propertyId',
            'propertiesdatadictionaries.versionNumber',
            'propertiesdatadictionaries.status',
            'propertiesdatadictionaries.relationToOtherDataDictionaries',
            'properties.gopID',
            'properties.referenceDocumentGUID',
            'propertiesdatadictionaries.units',
            'propertiesdatadictionaries.nameEn',
            'propertiesdatadictionaries.namePt',
            'propertiesdatadictionaries.nameEnSc',
            'propertiesdatadictionaries.namePtSc',
            'properties.visualRepresentation'
        )
            ->get();

        // Mark properties from the master template with `from_master` flag
        $joined_properties->each(function ($property) use ($masterpdt) {
            $property->from_master = ($property->pdtID == $masterpdt->Id); // Set true if from master, false otherwise
        });

        // Merge PDT and Master Groups
        $merged_groups = $pdt_groups->merge($master_groups);

        // Combine groups with similar names by grouping by gopNamePt
        $combinedGroups = $merged_groups->groupBy('gopNamePt');

        $group_order = [
            'Dados de classificação',
            'Dados gerais',
            'Dados do fabricante',
            'Dados de desempenho',
            'Dados de especificação',
            'Dados geométricos'
        ];

        // Sort groups based on the specified order, placing unmatched ones after the specified ones
        $sorted_combined_groups = $combinedGroups->sortBy(function ($_, $key) use ($group_order) {
            $index = array_search($key, $group_order);
            return $index === false ? count($group_order) : $index;
        });

        // Move 'Dados de gestão de instalações' and 'Dados de sustentabilidade' to the end
        $combined_groups = $sorted_combined_groups->sortBy(function ($_, $key) {
            if ($key === 'Dados de gestão de instalações') {
                return PHP_INT_MAX - 1; // Second to last position
            }
            if ($key === 'Dados de sustentabilidade') {
                return PHP_INT_MAX; // Last position
            }
            return 0; // Default ordering for others
        });

        $comments = comments::with('user')->get();


        $answers = Answers::where('users_id', Auth::id())->get();


        return view('pdtssurvey', compact('combined_groups', 'joined_properties', 'properties_dict', 'pdt', 'referenceDocument', 'comments', 'answers', 'properties', 'latestPdt'));
    }


    public function getGOPDataDictionary($gopID, $gopGUID)
    {

        $gopdd = groupofproperties::WHERE('Id', $gopID)
            ->first();
        $gopinpdts = groupofproperties::where('GUID', $gopGUID)
            ->get();
        $pdts = productdatatemplates::get();
        $gopversions = groupofproperties::where('GUID', $gopGUID)->get();


        return view('datadictionaryviewGOP', compact('gopdd', 'gopinpdts', 'pdts', 'gopversions'));
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'body' => 'required|string',
            'properties_Id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'errors' => 'O campo de feedback é obrigatório.'
            ]);
        } else {
            $propertyId = $request->input('properties_Id');
            $commentbody = $request->input('body');

            $pdtId = properties::where('Id', $propertyId)
                ->value('pdtID');

            $propertyGUID = properties::where('Id', $propertyId)
                ->value('GUID');

            $propertyName = propertiesdatadictionaries::where('GUID', $propertyGUID)
                ->where('versionNumber', function ($query) use ($propertyGUID) {
                    $query->selectRaw('MAX(versionNumber)')
                        ->from('propertiesdatadictionaries')
                        ->where('GUID', $propertyGUID);
                })
                ->where('revisionNumber', function ($query) use ($propertyGUID) {
                    $query->selectRaw('MAX(revisionNumber)')
                        ->from('propertiesdatadictionaries')
                        ->where('GUID', $propertyGUID)
                        ->where('versionNumber', function ($query) use ($propertyGUID) {
                            $query->selectRaw('MAX(versionNumber)')
                                ->from('propertiesdatadictionaries')
                                ->where('GUID', $propertyGUID);
                        });
                })
                ->value('namePt');

            $pdtName = productdatatemplates::where('id', $pdtId)
                ->value('pdtNamePt');

            $userIds = comments::join('properties', 'comments.properties_Id', '=', 'properties.Id')
                ->where('properties.Id', $propertyId)
                ->pluck('comments.users_id')
                ->unique()
                ->toArray();

            $emails = User::whereIn('id', $userIds)
                ->pluck('email')
                ->toArray();

            // $userName = User::where('id', Auth::id())->first('name');
            Mail::to('pdts.portugal@gmail.com')->send(new FeedbackMailAdmin($commentbody, $pdtName, $propertyName));
            foreach ($emails as $email) {
                // Only send the email if it doesn't belong to the logged in user
                if ($email !== Auth::user()->email) {
                    Mail::to($email)->send(new FeedbackMailUsers($commentbody, $pdtName, $propertyName));
                }
            }
            $comment = new comments;
            $comment->body = $request->input('body');
            $comment->properties_Id = $request->input('properties_Id');
            $comment->users_id = Auth::user()->id;
            $comment->save();

            $comment = comments::with('user')
                ->where('properties_Id', $propertyId)
                ->orderBy('created_at', 'desc')
                ->limit(1)
                ->get();

            return response()->json([
                'status' => 200,
                'message' => 'Comentário adicionado com sucesso.',
                'comment' => $comment,

            ]);
        }
    }

    public function saveAnswers(Request $request)
    {
        $userId = Auth::user()->id;
        $pdtName = $request->input('pdtName');
        Mail::to('pdts.portugal@gmail.com')->send(new SurveyMailAdmin($pdtName));

        $answers = $request->except('pdtName');

        foreach ($answers as $properties_id => $answer) {
            if ($properties_id != '_token') {
                $existingAnswer = Answers::where('users_id', $userId)
                    ->where('properties_id', $properties_id)
                    ->first();

                if ($existingAnswer) {
                    // An answer already exists for this property and user
                    if ($existingAnswer->answer != $answer) {
                        // The submitted answer is different, so update the existing answer
                        $existingAnswer->answer = $answer;
                        $existingAnswer->save();
                    }
                } else {
                    // No existing answer for this property and user, so create a new answer
                    $newAnswer = new Answers;
                    $newAnswer->answer = $answer;
                    $newAnswer->properties_id = $properties_id;
                    $newAnswer->users_id = $userId;
                    $newAnswer->save();
                }
            }
        }

        return redirect()->back()->with('success', 'Respostas guardadas com sucesso');
    }


    public function fetchfeedback($propertyId)
    {

        $comments = comments::select()->where('properties_Id', $propertyId);
        return response()->json([
            'comments' => $comments,
        ]);
    }

    public function destroyfeedback(Request $request)
    {
        $commentId = $request->input('comment_id');
        $comment = comments::where('id', $commentId);
        if ($comment) {
            $comment->delete();
            return response()->json([
                'status' => 200,
                'message' => 'Comentário Apagado com Sucesso.',
                'comment_id' => $commentId
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'message' => 'Não foi encontrado feedback.'
            ]);
        }
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\comments  $comments
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
        $comment = comments::find($id);
        $comment->body = $request->input('body');
        $comment->save();
        return redirect()->back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\comments  $comments
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
        $comment = comments::find($id);
        $comment->delete();
        return redirect()->back();
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

    public function createStep1()
    {
        // Get the latest versions/revisions of PDTs
        $pdts = productdatatemplates::get();


        return view('groupofproperties.choose_pdt', compact('pdts'));
    }

    public function createStep2(Request $request)
    {
        // Validate the request
        $request->validate([
            'pdtId' => 'required|exists:productdatatemplates,Id',
        ]);

        $pdtId = $request->input('pdtId');
        $referenceDocuments = ReferenceDocuments::all();
        // Get the selected PDT and its associated groups of properties
        $selectedPdt = productdatatemplates::find($pdtId);
        $associatedGroups = GroupOfProperties::where('pdtId', $pdtId)->get();

        return view('groupofproperties.creategop', compact('selectedPdt', 'associatedGroups', 'referenceDocuments'));
    }
    public function create()
    {
        //
    }

    public function storegop(Request $request)
    {
        // Validate the request
        $request->validate([
            'pdtId' => 'required|exists:productdatatemplates,Id',
            'gopNameEn' => 'required|string',
            'gopNamePt' => 'required|string',
            // Add other fields validation as needed
        ]);

        // Create a new Group of Properties
        $groupOfProperties = new GroupOfProperties();
        $groupOfProperties->pdtId = $request->input('pdtId');
        $groupOfProperties->gopNameEn = $request->input('gopNameEn');
        $groupOfProperties->gopNamePt = $request->input('gopNamePt');
        $groupOfProperties->GUID = $request->input('GUID');
        // Set other fields as needed
        $groupOfProperties->definitionEn = $request->input('definitionEn');
        $groupOfProperties->definitionPt = $request->input('definitionPt');
        $groupOfProperties->status = $request->input('status');
        $groupOfProperties->referenceDocumentGUID = $request->input('referenceDocumentGUID');
        $groupOfProperties->dateOfCreation = $request->input('dateOfCreation');
        $groupOfProperties->dateofActivation = $request->input('dateofActivation');
        $groupOfProperties->dateOfLastChange = $request->input('dateOfLastChange');
        $groupOfProperties->dateOfRevision = $request->input('dateOfRevision');
        $groupOfProperties->dateOfVersion = $request->input('dateOfVersion');
        $groupOfProperties->created_at = $request->input('created_at');
        $groupOfProperties->updated_at = $request->input('updated_at');
        $groupOfProperties->versionNumber = $request->input('versionNumber');
        $groupOfProperties->revisionNumber = $request->input('revisionNumber');
        $groupOfProperties->listOfReplacedProperties = $request->input('listOfReplacedProperties');
        $groupOfProperties->listOfReplacingProperties = $request->input('listOfReplacingProperties');
        $groupOfProperties->relationToOtherDataDictionaries = $request->input('relationToOtherDataDictionaries');
        $groupOfProperties->creatorsLanguage = $request->input('creatorsLanguage');
        $groupOfProperties->visualRepresentation = $request->input('visualRepresentation');
        $groupOfProperties->countryOfUse = $request->input('countryOfUse');
        $groupOfProperties->countryOfOrigin = $request->input('countryOfOrigin');
        $groupOfProperties->categoryOfGroupOfProperties = $request->input('categoryOfGroupOfProperties');
        $groupOfProperties->parentGroupOfProperties = $request->input('parentGroupOfProperties');
        // Add other fields as needed

        $groupOfProperties->save();

        return redirect()->route('groupofproperties.creategop', ['pdtId' => $request->input('pdtId')])
            ->with('success', 'Group of Properties added successfully!');
    }



    /**
     * Display the specified resource.
     *
     * @param  \App\Models\groupofproperties  $groupofproperties
     * @return \Illuminate\Http\Response
     */
    public function show(groupofproperties $groupofproperties)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\groupofproperties  $groupofproperties
     * @return \Illuminate\Http\Response
     */
    public function edit(groupofproperties $groupofproperties)
    {
        //
    }
}
