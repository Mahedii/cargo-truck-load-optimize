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
                                            <li class="breadcrumb-item"><a href="javascript: void(0);">User Lists</a></li>
                                            <li class="breadcrumb-item active">Edit User</li>
                                        </ol>
                                    </div>

                                </div>
                            </div>
                        </div>
                        <!-- end page title -->

                        <div class="row">
                            <div class="col-xxl-12">

                                @if(session('cus-user-edit-msg'))
                                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                                        <strong>{{ session('cus-user-edit-msg') }}</strong>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                @endif

                                <div class="card">
                                    <div class="card-header align-items-center d-flex">
                                        <h4 class="card-title mb-0 flex-grow-1">Edit User</h4>

                                    </div><!-- end card header -->

                                    <div class="card-body">

                                        <div class="live-preview">
                                            <form method="POST" action="{{ url('users/edit-custom-user/'.$editUser->id) }}">
                                                @csrf
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label for="nameInput" class="form-label">Name</label>
                                                            <input type="text" class="form-control" placeholder="Enter your name" name="nameInput" value="{{ $editUser->name }}">
                                                            @if ($errors->has('nameInput'))
                                                                <span class="text-danger">{{ $errors->first('nameInput') }}</span>
                                                            @endif
                                                        </div>

                                                    </div><!--end col-->

                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label for="emailInput" class="form-label">Email Address</label>
                                                            <input type="email" class="form-control" name="emailInput" value="{{ $editUser->email }}" disabled>

                                                        </div>

                                                    </div><!--end col-->

                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label for="emailidInput" class="form-label">Password</label>
                                                            <input type="password" class="form-control" name="passwordInput" value="{{ $editUser->password }}">
                                                            @if ($errors->has('passwordInput'))
                                                                <span class="text-danger">{{ $errors->first('passwordInput') }}</span>
                                                            @endif
                                                        </div>

                                                    </div><!--end col-->


                                                    {{-- <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label for="roleInput" class="form-label">Role</label>

                                                            {!! Form::select('roles[]', $roles,$userRole, array('class' => 'js-example-basic-multiple','multiple'=>'multiple')) !!}


                                                        </div>

                                                    </div> --}}


                                                    <div class="col-lg-12">
                                                        <div class="text-end">
                                                            <button type="submit" class="btn btn-primary">Update</button>
                                                        </div>
                                                    </div><!--end col-->
                                                </div><!--end row-->
                                            </form>
                                        </div>

                                    </div>
                                </div>
                            </div> <!-- end col -->

                        </div><!--end row-->


                    </div> <!-- container-fluid -->
                </div>
                <!-- End Page-content -->


@include('admin.include.footer')
