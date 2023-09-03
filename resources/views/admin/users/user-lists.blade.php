@include('admin.include.header')

                <div class="page-content">
                    <div class="container-fluid">

                        <!-- start page title -->
                        <div class="row">
                            <div class="col-12">
                                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                                    <h4 class="mb-sm-0">User Management</h4>
                                    <div class="page-title-right">
                                        <ol class="breadcrumb m-0">
                                            <li class="breadcrumb-item"><a href="javascript: void(0);">User Management</a></li>
                                            <li class="breadcrumb-item active">User Lists</li>
                                        </ol>
                                    </div>

                                </div>
                            </div>
                        </div>
                        <!-- end page title -->


                        <div class="row">
                            <div class="col-lg-12">

                                @if(session('user-del-msg'))
                                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                                        <strong>{{ session('user-del-msg') }}</strong>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                @endif

                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">User Lists</h5>
                                    </div>
                                    <div class="card-body">
                                        <table id="example"
                                            class="table table-bordered dt-responsive nowrap table-striped align-middle"
                                            style="width:100%">
                                            <thead>
                                                <tr>
                                                    <th>No</th>
                                                    <th>Name</th>
                                                    <th>Email</th>
                                                    <!-- <th>Password</th> -->
                                                    {{-- <th>Role</th> --}}
                                                    <th>Create Date</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>

                                                @php($i = 1)
                                                @foreach($userInformation as $ui)

                                                    <tr>
                                                        <td>{{ $i }}</td>
                                                        <td>{{ $ui->name }}</td>
                                                        <td>{{ $ui->email }}</td>
                                                        <!-- <td>{{ $ui->password }}</td> -->
                                                        {{-- <td>
                                                            @php($getUserRole = App\Http\Controllers\Admin\UserController::getUserRole($ui->id))


                                                            @foreach($getUserRole as $gur)
                                                                <span class="badge text-bg-success">{{ $gur }}</span>
                                                            @endforeach

                                                        </td> --}}
                                                        <td>{{ Carbon\Carbon::parse($ui->created_at)->diffForHumans() }}</td>
                                                        <td>
                                                            <div class="dropdown d-inline-block">
                                                                <button class="btn btn-soft-secondary btn-sm dropdown" type="button"
                                                                    data-bs-toggle="dropdown" aria-expanded="false">
                                                                    <i class="ri-more-fill align-middle"></i>
                                                                </button>
                                                                <ul class="dropdown-menu dropdown-menu-end">
                                                                    <!-- <li>
                                                                        <a href="#!" class="dropdown-item">
                                                                            <i class="ri-eye-fill align-bottom me-2 text-muted"></i>
                                                                            View
                                                                        </a>
                                                                    </li> -->

                                                                    <li>
                                                                        <a href="{{ route('user.edit',$ui->id) }}" class="dropdown-item edit-item-btn">
                                                                            <i class="ri-pencil-fill align-bottom me-2 text-muted"></i>
                                                                            Edit
                                                                        </a>
                                                                    </li>

                                                                    <li>
                                                                        <a href="{{ route('user.delete',$ui->id) }}" class="dropdown-item edit-item-btn">
                                                                            <i class="ri-delete-bin-fill align-bottom me-2 text-muted"></i>
                                                                            Delete
                                                                        </a>
                                                                    </li>
                                                                </ul>
                                                            </div>
                                                        </td>
                                                    </tr>

                                                    @php($i++)
                                                @endforeach

                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>


                    </div> <!-- container-fluid -->
                </div>
                <!-- End Page-content -->


@include('admin.include.footer')
