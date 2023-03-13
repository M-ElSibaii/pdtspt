<x-app-layout>

    <main class="flex-shrink-0">
        <div class="py-9">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div class="p-4  mx-auto bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                    <div class="home_content container">

                        <strong>
                            <h1>Users</h1>
                        </strong>
                        <form method="POST" action="{{ route('update.users') }}">
                            @csrf
                            <table id="tblpdts">
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
                                        <td>{{ $user->id }}</td>
                                        <td>{{ $user->name }}</td>
                                        <td>{{ $user->email }}</td>
                                        <td>{{ $user->profession }}</td>
                                        <td>{{ $user->institute }}</td>
                                        <td>
                                            <input type="radio" name="isAdmin[{{ $user->id }}]" value="1" {{ $user->isAdmin ? 'checked' : '' }}>
                                            <label for="isAdmin_{{ $user->id }}_yes">Yes</label>
                                            <input type="radio" name="isAdmin[{{ $user->id }}]" value="0" {{ !$user->isAdmin ? 'checked' : '' }}>
                                            <label for="isAdmin_{{ $user->id }}_no">No</label>
                                        </td>
                                        <td>
                                            <input type="radio" name="subscribe[{{ $user->id }}]" value="1" {{ $user->subscribe ? 'checked' : '' }}>
                                            <label for="subscribe{{ $user->id }}_yes">Yes</label>
                                            <input type="radio" name="subscribe[{{ $user->id }}]" value="0" {{ !$user->subscribe ? 'checked' : '' }}>
                                            <label for="subscribe{{ $user->id }}_no">No</label>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <div class="mt-6">
                                {{ $users->links() }}
                            </div>
                            <br>
                            <button class="btn btn-primary" type="submit">Save</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>


        <div class="ml-2 text-center text-sm text-gray-500 sm:text-right sm:ml-0">
            © 2021 UMinho. All rights reserved. <a href="{{route('privacypolicy')}}"> Política de privacidade</a>
            <p></p>
        </div>
    </main>

</x-app-layout>