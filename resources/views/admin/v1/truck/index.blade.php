@extends('admin.include.master')
    @section('content')

        <div class="page-content">
            <div class="container-fluid">

                <!-- start page title -->
                <div class="row">
                    <div class="col-12">
                        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                            <h4 class="mb-sm-0">Truck-Info</h4>
                            <div class="page-title-right">
                                <ol class="breadcrumb m-0">
                                    <li class="breadcrumb-item"><a href="javascript: void(0);">Truck-Info</a></li>
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


                    <form method="POST" action="{{ route('truckList.addData') }}" enctype="multipart/form-data">

                        @csrf

                        <div class="row">
                            <div class="col-lg-12">

                                <div class="card">

                                    <div class="card-header align-items-center d-flex">
                                        <h4 class="card-title mb-0 flex-grow-1">Truck</h4>

                                    </div><!-- end card header -->

                                    <div class="card-body">

                                        <div class="row">

                                            <div class="col-md-12">
                                                <div class="mb-3">
                                                    <label for="truck_type" class="form-label">Truck Type <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control @error('truck_type') is-invalid @enderror" value="{{ old('truck_type') }}" name="truck_type">
                                                    @if ($errors->has('truck_type'))
                                                        <span class="text-danger">{{ $errors->first('truck_type') }}</span>
                                                    @endif
                                                </div>
                                            </div>

                                            <div class="col-md-12">
                                                <div class="mb-3">
                                                    <label for="length" class="form-label">Truck Length <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control @error('length') is-invalid @enderror" value="{{ old('length') }}" name="length">
                                                    @if ($errors->has('length'))
                                                        <span class="text-danger">{{ $errors->first('length') }}</span>
                                                    @endif
                                                </div>
                                            </div>

                                            <div class="col-md-12">
                                                <div class="mb-3">
                                                    <label for="width" class="form-label">Truck Width <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control @error('width') is-invalid @enderror" value="{{ old('width') }}" name="width">
                                                    @if ($errors->has('width'))
                                                        <span class="text-danger">{{ $errors->first('width') }}</span>
                                                    @endif
                                                </div>
                                            </div>

                                            <div class="col-md-12">
                                                <div class="mb-3">
                                                    <label for="height" class="form-label">Truck Height <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control @error('height') is-invalid @enderror" value="{{ old('height') }}" name="height">
                                                    @if ($errors->has('height'))
                                                        <span class="text-danger">{{ $errors->first('height') }}</span>
                                                    @endif
                                                </div>
                                            </div>

                                            <div class="col-md-12">
                                                <div class="mb-3">
                                                    <label for="max_weight" class="form-label">Truck Weight <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control @error('max_weight') is-invalid @enderror" value="{{ old('max_weight') }}" name="max_weight">
                                                    @if ($errors->has('max_weight'))
                                                        <span class="text-danger">{{ $errors->first('max_weight') }}</span>
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
                                <h5 class="card-title mb-0">Truck Info</h5>
                            </div>

                            <div class="card-body">
                                <table id="example" class="table table-bordered dt-responsive nowrap table-striped align-middle" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Name</th>
                                            <th>Dimension</th>
                                            <th>Weight</th>
                                            <th>Created</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>

                                        @foreach($trucksData as $key => $data)

                                            <tr data-id="{{ $data->id }}" class="truck-{{ $data->id }}">
                                                <td>{{ ++$key }}</td>
                                                <td>{{ $data->truck_type }}</td>
                                                <td>{{ $data->length }}*{{ $data->width }}*{{ $data->height }}</td>
                                                <td>{{ $data->max_weight }}</td>
                                                <td>{{ Carbon\Carbon::parse($data->created_at)->diffForHumans() }}</td>
                                                <td>
                                                    <div class="dropdown d-inline-block">
                                                        <button class="btn btn-soft-secondary btn-sm dropdown" type="button"
                                                            data-bs-toggle="dropdown" aria-expanded="false">
                                                            <i class="ri-more-fill align-middle"></i>
                                                        </button>

                                                        <ul class="dropdown-menu dropdown-menu-end">
                                                            {{-- <li>
                                                                <a href="{{ route('truckList.load.selectedData', $data->slug) }}" class="dropdown-item edit-item-btn">
                                                                    <i class="ri-pencil-fill align-bottom me-2 text-muted"></i>
                                                                    Edit
                                                                </a>
                                                            </li>

                                                            <li>
                                                                <a href="{{ route('truckList.deleteData', $data->slug) }}" class="dropdown-item delete-item-btn" onclick="return confirm('Are you sure you want to delete this?');">
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
