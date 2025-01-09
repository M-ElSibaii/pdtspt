<x-app-layout>
    <div style="background-color: white;">
        <div class="container sm:max-w-full py-9">
            <div class="container">
                @if(session('success'))
                <div class="alert alert-success mt-3">
                    {{ session('success') }}
                </div>
                @endif
                <h1>{{ __('See reference documents') }}</h1>

                <div>
                    <!-- Search Bar -->
                    <input
                        type="text"
                        id="searchReferenceDocument"
                        class="form-control mb-3"
                        placeholder="{{ __('Search reference documents...') }}"
                        onkeyup="filterReferenceDocuments()" />

                    <!-- Scrollable Table of Reference Documents -->
                    <div
                        id="referenceDocumentTable"
                        style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr>
                                    <th style="border-bottom: 1px solid #ddd; padding: 8px; text-align: left;">{{ __('Name') }}</th>
                                    <th style="border-bottom: 1px solid #ddd; padding: 8px; text-align: left;">{{ __('Title') }}</th>
                                    <th style="border-bottom: 1px solid #ddd; padding: 8px; text-align: left;">{{ __('View') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Populate table rows with reference documents, excluding 'n/a' -->
                                @foreach ($rds as $document)
                                @if (strtolower($document->rdName) !== 'n/a')
                                <tr>
                                    <td style="padding: 8px; border-bottom: 1px solid #ddd;">{{ $document->rdName }}</td>
                                    <td style="padding: 8px; border-bottom: 1px solid #ddd;">{{ $document->title }}</td>
                                    <td style="padding: 8px; border-bottom: 1px solid #ddd;">
                                        <a
                                            href="{{ route('referencedocumentview', ['rdGUID' => $document->GUID]) }}"
                                            style="color: blue; text-decoration: underline;">
                                            {{ __('View') }}
                                        </a>
                                    </td>
                                </tr>
                                @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <br>


                <h1>{{ __('Add Reference Document') }}</h1>

                <form method="POST" action="{{ route('referencedocuments.create') }}">
                    @csrf

                    <!-- GUID Field -->
                    <div class="form-group">
                        <label for="GUID">{{ __('GUID') }}</label>
                        <input type="text" class="form-control" id="GUID" name="GUID" placeholder="{{ __('Enter document GUID') }}" required>
                    </div>

                    <!-- Name Field -->
                    <div class="form-group">
                        <label for="name">{{ __('Name') }}</label>
                        <input type="text" class="form-control" id="name" name="name" placeholder="{{ __('Enter document name') }}" required>
                    </div>

                    <!-- Title Field -->
                    <div class="form-group">
                        <label for="title">{{ __('Title') }}</label>
                        <input
                            type="text" class="form-control" id="title" name="title" placeholder="{{ __('Enter document title') }}" required>
                    </div>

                    <!-- Description Field -->
                    <div class="form-group">
                        <label for="description">{{ __('Description') }}</label>
                        <textarea
                            class="form-control" id="description" name="description" rows="4" placeholder="{{ __('Enter a brief description') }}"></textarea>
                    </div>

                    <!-- Status Field -->
                    <div class="form-group">
                        <label for="status">{{ __('Status') }}</label>
                        <input type="text" class="form-control" id="status" name="status">
                    </div>

                    <!-- Submit Button -->
                    <x-secondary-button type="submit">
                        {{ __('Add Reference Document') }}
                    </x-secondary-button>
                </form>

            </div>
            <script>
                // JavaScript function to filter the list
                function filterReferenceDocuments() {
                    const searchInput = document.getElementById('searchReferenceDocument').value.toLowerCase();
                    const listItems = document.querySelectorAll('#referenceDocumentList .reference-document-item');

                    listItems.forEach(item => {
                        const name = item.dataset.name.toLowerCase();
                        item.style.display = name.includes(searchInput) ? '' : 'none';
                    });
                }
            </script>

</x-app-layout>