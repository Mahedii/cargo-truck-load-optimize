@extends('admin.include.master')
    @section('content')

        <div class="page-content">
            <div class="container-fluid">

                <!-- start page title -->
                <div class="row">
                    <div class="col-12">
                        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                            <h4 class="mb-sm-0">Profile</h4>
                            <div class="page-title-right">
                                <ol class="breadcrumb m-0">
                                    <li class="breadcrumb-item"><a href="javascript: void(0);">Profile</a></li>
                                    <li class="breadcrumb-item active">Add</li>
                                </ol>
                            </div>

                        </div>
                    </div>
                </div>
                <!-- end page title -->

                <div class="row">

                    <div class="border-0">
                        <div class="row g-4">

                            <div class="col-sm" style="margin-bottom: 1rem;">
                                @if(session('crudMsg'))
                                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                                        <strong>{{ session('crudMsg') }}</strong>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                @endif
                            </div>

                            <div class="col-sm" style="margin-bottom: 1rem;">
                                <div class="d-flex justify-content-sm-end">
                                    <a href="{{ url()->previous() }}" class="btn btn-success" id="addproduct-btn">
                                        <i class="ri-arrow-left-line align-bottom me-1"></i>
                                        Back
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ route('userProfile.updateData') }}" enctype="multipart/form-data">

                    @csrf

                    <div class="row">

                        <div class="col-xxl-3">
                            <div class="card card-bg-fill">
                                <div class="card-body p-4">
                                    <div class="text-center">
                                        <div class="profile-user position-relative d-inline-block mx-auto  mb-4">
                                            <img src="@if (Auth::user()->avatar != '') {{ URL::asset('admin/assets/images/users/' . Auth::user()->id . '/' . Auth::user()->avatar) }}@else{{ URL::asset('admin/assets/images/users/avatar-3.jpg') }} @endif"
                                                class="  rounded-circle avatar-xl img-thumbnail user-profile-image"
                                                alt="user-profile-image">
                                            <div class="avatar-xs p-0 rounded-circle profile-photo-edit">
                                                <input id="profile-img-file-input" type="file" class="profile-img-file-input @error('title') is-invalid @enderror" name="singleFile">
                                                <label for="profile-img-file-input" class="profile-photo-edit avatar-xs">
                                                    <span class="avatar-title rounded-circle bg-light text-body">
                                                        <i class="ri-camera-fill"></i>
                                                    </span>
                                                </label>
                                                @if ($errors->has('singleFile'))
                                                    <span class="text-danger">{{ $errors->first('singleFile') }}</span>
                                                @endif
                                            </div>
                                        </div>
                                        <h5 class="fs-16 mb-1">{{ Auth::user()->name }}</h5>
                                    </div>
                                </div>
                            </div>
                            <!--end card-->
                        </div>

                        <div class="col-xxl-9">
                            <div class="card">
                                <div class="card-header">
                                    <ul class="nav nav-tabs-custom rounded card-header-tabs border-bottom-0" role="tablist">
                                        <li class="nav-item">
                                            <a class="nav-link active" data-bs-toggle="tab" href="#personalDetails" role="tab">
                                                <i class="fas fa-home"></i>
                                                Personal Details
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                                <div class="card-body p-4">
                                    <div class="tab-content">
                                        <div class="tab-pane active" id="personalDetails" role="tabpanel">
                                            <div class="row">
                                                <div class="col-lg-6">
                                                    <div class="mb-3">
                                                        <label for="nameInput" class="form-label">Name</label>
                                                        <input type="text" class="form-control @error('title') is-invalid @enderror" id="nameInput" name="name" placeholder="Enter your name" value="{{ Auth::user()->name }}">
                                                        @if ($errors->has('name'))
                                                            <span class="text-danger">{{ $errors->first('name') }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                                <!--end col-->
                                                <div class="col-lg-6">
                                                    <div class="mb-3">
                                                        <label for="emailInput" class="form-label">Email Address</label>
                                                        <input type="email" class="form-control @error('title') is-invalid @enderror" id="emailInput" name="email"
                                                            placeholder="Enter your email" value="{{ Auth::user()->email }}">
                                                            @if ($errors->has('email'))
                                                                <span class="text-danger">{{ $errors->first('email') }}</span>
                                                            @endif
                                                    </div>
                                                </div>
                                                <!--end col-->
                                            </div>
                                            <div class="row g-2">
                                                <div class="col-lg-4">
                                                    <div>
                                                        <label for="oldpasswordInput" class="form-label">Old Password*</label>
                                                        <input type="password" class="form-control" id="oldpasswordInput"
                                                            placeholder="Enter current password" value="{{ Auth::user()->password }}">
                                                    </div>
                                                </div>
                                                <!--end col-->
                                                <div class="col-lg-4">
                                                    <div>
                                                        <label for="newpasswordInput" class="form-label">New Password*</label>
                                                        <input type="password" class="form-control @error('title') is-invalid @enderror" id="newpasswordInput" name="password" placeholder="Enter new password">
                                                        @if ($errors->has('password'))
                                                            <span class="text-danger">{{ $errors->first('password') }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                                <!--end col-->

                                                <div class="col-lg-12">
                                                    <div class="hstack gap-2 justify-content-end">
                                                        <button type="submit" class="btn btn-primary">Updates</button>
                                                        <button type="button" class="btn btn-soft-danger">Cancel</button>
                                                    </div>
                                                </div>
                                                <!--end col-->
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

            </form>

            </div>
            <!-- container-fluid -->
        </div>
        <!-- End Page-content -->

        @include('admin.v1.common.ajax.single-image')

        <script src="{{ URL::asset('admin/assets/js/pages/profile-setting.init.js') }}"></script>

    @endsection
