<x-app-layout>

    <div style="background-color: white;">
        <div class="container py-9">
            <h1>Users</h1>
            <form method="POST" action="{{ route('update.users') }}">
                @csrf
                <table class="table-auto" id="tblpdts">
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Profession</th>
                            <th>Institute</th>
                            <th>Is Admin?</th>
                            <th>Subscribed?</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                        <tr>
                            <td class="p-1.5">{{ $user->id }}</td>
                            <td class="p-1.5">{{ $user->name }}</td>
                            <td class="p-1.5">{{ $user->email }}</td>
                            <td class="p-1.5">{{ $user->profession }}</td>
                            <td class="p-1.5">{{ $user->institute }}</td>
                            <td class="p-1.5">
                                <div class="form-check form-check-inline">
                                    <input class="h-4 w-4 border-gray-300 text-slate-600 focus:ring-slate-600" type="radio" name="isAdmin[{{ $user->id }}]" value="1" {{ $user->isAdmin ? 'checked' : '' }}>
                                    <label class="ml-2 my-auto block text-sm font-medium leading-6 text-gray-900" for="isAdmin_{{ $user->id }}_yes">Yes</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="h-4 w-4 border-gray-300 text-slate-600 focus:ring-slate-600" type="radio" name="isAdmin[{{ $user->id }}]" value="0" {{ !$user->isAdmin ? 'checked' : '' }}>
                                    <label class="ml-2 my-auto block text-sm font-medium leading-6 text-gray-900" for="isAdmin_{{ $user->id }}_no">No</label>
                                </div>
                            </td>
                            <td class="p-1.5">
                                <div class="form-check form-check-inline">
                                    <input class="h-4 w-4 border-gray-300 text-slate-600 focus:ring-slate-600" type="radio" name="subscribe[{{ $user->id }}]" value="1" {{ $user->subscribe ? 'checked' : '' }}>
                                    <label class="ml-2 my-auto block text-sm font-medium leading-6 text-gray-900" for="subscribe{{ $user->id }}_yes">Yes</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="h-4 w-4 border-gray-300 text-slate-600 focus:ring-slate-600" type="radio" name="subscribe[{{ $user->id }}]" value="0" {{ !$user->subscribe ? 'checked' : '' }}>
                                    <label class="ml-2 my-auto block text-sm font-medium leading-6 text-gray-900" for="subscribe{{ $user->id }}_no">No</label>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="mt-6">
                    {{ $users->links() }}
                </div>
                <br>
                <x-button-primary-pdts link="{{route('dashboard')}}" type="submit" title="Save" />
            </form>
            <br>
            <a href="{{ route('pdtinput') }}" class="btn btn-secondary">
                Create PDTs
            </a>
            <br>
            <br>
            <!-- export all the pdts with all versions // next step is to filter only the latest version -->
            <a href="{{ route('exportdomainbsdd') }}" class="btn btn-secondary">
                Export PDTs.pt domain in bsdd JSON format page
            </a>
        </div>
    </div>

</x-app-layout>