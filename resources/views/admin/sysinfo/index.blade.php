
<table class="table table-bordered">
    <thead>
        <tr>
            <th>项目</th>
            <th>值</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($data as $key => $value)
            <tr>
                <td>{{ $key }}</td>
                <td>{{ $value }}</td>
            </tr>
        @endforeach
    </tbody>
</table>