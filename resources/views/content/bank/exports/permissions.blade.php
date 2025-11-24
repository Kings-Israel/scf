<table>
  <thead>
    <tr>
      <th>Module</th>
      <th>Rights/Permissions</th>
    </tr>
  </thead>
  <tbody>
    @foreach ($permissions as $permission)
      <tr>
        <td>
          {{ $permission['name'] }}
        </td>
        @foreach ($permission['groups'] as $group)
          @foreach ($group['access_groups'] as $access_group)
            <td>
              {{ $access_group['name'] }}
            </td>
          @endforeach
        @endforeach
      </tr>
    @endforeach
  </tbody>
</table>
