<div class="form-actions -inline">
    <form method="GET" action="{{ route('users.search')}}">
        {{ csrf_field() }}

        <li>
            {{-- @NOTE: The inline-style here is just temporary for now. We should look into this possibly being a react component. --}}
            <input class="text-field -search" type="text" name="query" placeholder="Search by full email, mobile, Northstar ID..." style="min-width: 400px;" />
        </li>

        <li>
            <input type="submit" class="button" value="Submit" />
        </li>
    </form>
</div>
