<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        \App\Models\productdatatemplates::create([
            'pdtNameEn' => 'Master',
            'pdtNamePt' => 'Mestre',
            'GUID' => '1',
            'versionNumber' => '0',
            'revisionNumber' => '0',
            'dateOfRevision' => '2023-01-16 11:45:01',
            'dateOfVersion' => '2023-01-16 11:45:01'

        ]);
        \App\Models\referencedocuments::create([
            'GUID' => '2',
            'rdName' => 'reference document test',

        ]);
        \App\Models\groupofproperties::create([

            'GUID' => '3',
            'pdtId' => '1',
            'gopNameEn' => 'classification',
            'gopNamePt' => 'classificao',
            'versionNumber' => '0',
            'revisionNumber' => '0',
            'dateOfRevision' => '2023-01-16 11:45:01',
            'dateOfVersion' => '2023-01-16 11:45:01'

        ]);

        \App\Models\propertiesdatadictionaries::create(
            [

                'GUID' => '4',
                'nameEn' => 'property 1',
                'namePt' => 'propreidade 1',
                'versionNumber' => '0',
                'revisionNumber' => '0',
                'dateOfRevision' => '2023-01-16 11:45:01',
                'dateOfVersion' => '2023-01-16 11:45:01'

            ]
        );
        \App\Models\propertiesdatadictionaries::create(
            [

                'GUID' => '5',
                'nameEn' => 'property 2',
                'namePt' => 'propreidade 2',
                'versionNumber' => '0',
                'revisionNumber' => '0',
                'dateOfRevision' => '2023-01-16 11:45:01',
                'dateOfVersion' => '2023-01-16 11:45:01'

            ]
        );
        \App\Models\propertiesdatadictionaries::create(
            [

                'GUID' => '6',
                'nameEn' => 'property 3',
                'namePt' => 'propreidade 3',
                'versionNumber' => '0',
                'revisionNumber' => '0',
                'dateOfRevision' => '2023-01-16 11:45:01',
                'dateOfVersion' => '2023-01-16 11:45:01'

            ]
        );

        \App\Models\properties::create([

            'GUID' => '4',
            'pdtID' => '1',
            'gopID' => '1',
            'descriptionEn' => 'n/a',
            'descriptionPt' => 'n/a pt',
            'referenceDocumentGUID' => '2'

        ]);
        \App\Models\properties::create([

            'GUID' => '5',
            'pdtID' => '1',
            'gopID' => '1',
            'descriptionEn' => 'n/a 2',
            'descriptionPt' => 'n/a 2 pt',
            'referenceDocumentGUID' => '2'

        ]);
        \App\Models\properties::create([

            'GUID' => '6',
            'pdtID' => '1',
            'gopID' => '1',
            'descriptionEn' => 'n/a 3',
            'descriptionPt' => 'n/a  3pt',
            'referenceDocumentGUID' => '2'

        ]);

        \App\Models\comments::create([

            'parent_id' => '0',
            'users_id' => '6',
            'properties_Id' => '1',
            'body' => 'This is the body of the 1 comment',
            'published_at' => '2023-01-16 11:45:01',

        ]);

        \App\Models\comments::create([

            'parent_id' => '0',
            'users_id' => '5',
            'properties_Id' => '2',
            'body' => 'This is the body of the 1 comment',
            'published_at' => '2023-01-16 12:45:01',

        ]);
        \App\Models\comments::create([

            'parent_id' => '1',
            'users_id' => '3',
            'properties_Id' => '1',
            'body' => 'This is the reply on the 1 comment using parent id',
            'published_at' => '2023-01-16 11:45:01',

        ]);
    }
}
