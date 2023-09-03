@extends('admin.include.master')
    @section('content')

        <div class="page-content">
            <div class="container-fluid">

                <!-- start page title -->
                <div class="row">
                    <div class="col-12">
                        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                            <h4 class="mb-sm-0">Cargo-Info</h4>
                            <div class="page-title-right">
                                <ol class="breadcrumb m-0">
                                    <li class="breadcrumb-item"><a href="javascript: void(0);">Cargo-Info</a></li>
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


                    <form method="POST" action="{{ route('cargoInfo.addData') }}" enctype="multipart/form-data">

                        @csrf

                        <div class="row">
                            <div class="col-lg-12">

                                <div class="card">

                                    <div class="card-header align-items-center d-flex">
                                        <h4 class="card-title mb-0 flex-grow-1">Cargo</h4>

                                    </div><!-- end card header -->

                                    <div class="card-body">

                                        <div class="row">


                                            <div class="col-md-12">
                                                <div class="mb-3">
                                                    <label for="cargo_id" class="form-label">Cargo <span class="text-danger">*</span></label>
                                                    <select class="js-example-basic-single" id="select-cargo-id" name="cargo_id">
                                                        <option>Select Cargo</option>
                                                        @foreach($cargoListData as $data)
                                                            <option value="{{ $data->id }}">{{ $data->name }}</option>
                                                        @endforeach
                                                    </select>
                                                    @if ($errors->has('cargo_id'))
                                                        <span class="text-danger">{{ $errors->first('cargo_id') }}</span>
                                                    @endif
                                                </div>

                                            </div><!--end col-->

                                            <div class="col-md-12">
                                                <div class="mb-3">
                                                    <label for="length" class="form-label">Box Length <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control @error('length') is-invalid @enderror" value="{{ old('length') }}" name="length">
                                                    @if ($errors->has('length'))
                                                        <span class="text-danger">{{ $errors->first('length') }}</span>
                                                    @endif
                                                </div>
                                            </div>

                                            <div class="col-md-12">
                                                <div class="mb-3">
                                                    <label for="width" class="form-label">Box Width <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control @error('width') is-invalid @enderror" value="{{ old('width') }}" name="width">
                                                    @if ($errors->has('width'))
                                                        <span class="text-danger">{{ $errors->first('width') }}</span>
                                                    @endif
                                                </div>
                                            </div>

                                            <div class="col-md-12">
                                                <div class="mb-3">
                                                    <label for="height" class="form-label">Box Height <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control @error('height') is-invalid @enderror" value="{{ old('height') }}" name="height">
                                                    @if ($errors->has('height'))
                                                        <span class="text-danger">{{ $errors->first('height') }}</span>
                                                    @endif
                                                </div>
                                            </div>

                                            <div class="col-md-12">
                                                <div class="mb-3">
                                                    <label for="quantity" class="form-label">Box Quantity <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control @error('quantity') is-invalid @enderror" value="{{ old('quantity') }}" name="quantity">
                                                    @if ($errors->has('quantity'))
                                                        <span class="text-danger">{{ $errors->first('quantity') }}</span>
                                                    @endif
                                                </div>
                                            </div>

                                        </div>

                                    </div>
                                </div>
                                <!-- end card -->

                                <div class="text-end mb-3">
                                    <button type="submit" class="btn btn-success w-sm">Add</button>
                                </div>
                            </div>
                            <!-- end col -->


                        </div>
                        <!-- end row -->

                    </form>

                </div>

                <div class="row">
                    <div class="col-lg-12">

                        <div class="card">

                            <div class="card-header">
                                <h5 class="card-title mb-0">Cargo Info</h5>
                            </div>

                            <div class="card-body">
                                <table id="example" class="table table-bordered dt-responsive nowrap table-striped align-middle" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Cargo Name</th>
                                            <th>Dimension</th>
                                            <th>Quantity</th>
                                            <th>Created</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>


                                        @foreach($cargoInfoData as $key => $data)

                                            <tr data-id="{{ $data->id }}" class="cargo-box-{{ $data->id }}">
                                                <td>{{ ++$key }}</td>
                                                <td>{{ $data->cargo_id }}</td>
                                                <td>{{ $data->box_dimension }}</td>
                                                <td>{{ $data->quantity }}</td>
                                                <td>{{ Carbon\Carbon::parse($data->created_at)->diffForHumans() }}</td>
                                                <td>
                                                    <div class="dropdown d-inline-block">
                                                        <button class="btn btn-soft-secondary btn-sm dropdown" type="button"
                                                            data-bs-toggle="dropdown" aria-expanded="false">
                                                            <i class="ri-more-fill align-middle"></i>
                                                        </button>

                                                        <ul class="dropdown-menu dropdown-menu-end">
                                                            {{-- <li>
                                                                <a href="{{ route('cargoInfo.load.selectedData', $data->slug) }}" class="dropdown-item edit-item-btn">
                                                                    <i class="ri-pencil-fill align-bottom me-2 text-muted"></i>
                                                                    Edit
                                                                </a>
                                                            </li>

                                                            <li>
                                                                <a href="{{ route('cargoInfo.deleteData', $data->slug) }}" class="dropdown-item delete-item-btn" onclick="return confirm('Are you sure you want to delete this?');">
                                                                    <i class="ri-delete-bin-fill align-bottom me-2 text-muted"></i>
                                                                    Delete
                                                                </a>
                                                            </li> --}}

                                                        </ul>

                                                    </div>
                                                </td>
                                            </tr>

                                        @endforeach

                                    </tbody>
                                </table>

                            </div>
                        </div>
                    </div>
                </div>

            </div>
            <!-- container-fluid -->
        </div>
        <!-- End Page-content -->

    @endsection
