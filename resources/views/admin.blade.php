<x-app-layout>

    <div style="background-color: white;">
        <div class="container py-9">
            <h1>Users</h1>
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
                <x-button-primary-pdts 
                    link="{{route('dashboard')}}"
                    type="submit"
                    title="Save"/>   
            </form>
        </div>
    </div>

</x-app-layout>