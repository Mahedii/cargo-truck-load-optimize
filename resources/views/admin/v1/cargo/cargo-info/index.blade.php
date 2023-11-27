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
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <table class="table table-bordered dt-responsive nowrap table-striped align-middle" style="width:100%">
                                                <thead>
                                                    <tr>
                                                        <th>Cargo</th>
                                                        <th>Length</th>
                                                        <th>Width</th>
                                                        <th>Heigth</th>
                                                        <th>Quantity</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @for ($i = 0; $i < 10; $i++)
                                                        <tr>
                                                            <td>
                                                                <select class="js-example-basic-single" name="cargo_id[]">
                                                                    <option value="">Select Cargo</option>
                                                                    @foreach($cargoListData as $data)
                                                                        <option value="{{ $data->id }}" {{ old("cargo_id.$i", '') == $data->id ? 'selected' : '' }}>{{ $data->name }}</option>
                                                                    @endforeach
                                                                </select>
                                                                @error("cargo_id.$i")
                                                                    <p class="text-danger">{{ $message }}</p>
                                                                @enderror
                                                            </td>
                                                            <td>
                                                                <input type="text" name="length[]" value="{{ old('length.' . $i) }}">
                                                                @error("length.$i")
                                                                    <p class="text-danger">{{ $message }}</p>
                                                                @enderror
                                                            </td>
                                                            <td>
                                                                <input type="text" name="width[]" value="{{ old('width.' . $i) }}">
                                                                @error("width.$i")
                                                                    <p class="text-danger">{{ $message }}</p>
                                                                @enderror
                                                            </td>
                                                            <td>
                                                                <input type="text" name="height[]" value="{{ old('height.' . $i) }}">
                                                                @error("height.$i")
                                                                    <p class="text-danger">{{ $message }}</p>
                                                                @enderror
                                                            </td>
                                                            <td>
                                                                <input type="text" name="quantity[]" value="{{ old('quantity.' . $i) }}">
                                                                @error("quantity.$i")
                                                                    <p class="text-danger">{{ $message }}</p>
                                                                @enderror
                                                            </td>
                                                        </tr>
                                                    @endfor
                                                    <tr>
                                                        <td colspan="5">
                                                            <div class="text-end mb-3">
                                                                <button type="submit" class="btn btn-success w-sm">Add</button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>


                    {{-- <form method="POST" action="{{ route('cargoInfo.addData') }}" enctype="multipart/form-data">

                        @csrf

                        <div class="row">
                            <div class="col-lg-12">

                                <div class="card">

                                    <div class="card-header align-items-center d-flex">
                                        <h4 class="card-title mb-0 flex-grow-1">Cargo</h4>

                                    </div>

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

                                            </div>

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

                                <div class="text-end mb-3">
                                    <button type="submit" class="btn btn-success w-sm">Add</button>
                                </div>
                            </div>

                        </div>

                    </form> --}}

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
                                                <td>{{ $data->cargo_name }}</td>
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
                                                            </li> --}}

                                                            <a href="javascript:void(0);" data-slug="{{$data->slug}}" class="dropdown-item edit-item-btn ajax-edit-data-btn">
                                                                <i class="ri-pencil-fill align-bottom me-2 text-muted"></i>
                                                                Edit
                                                            </a>

                                                            <li>
                                                                <a href="{{ route('cargoInfo.deleteData', $data->slug) }}" class="dropdown-item delete-item-btn" onclick="return confirm('Are you sure you want to delete this?');">
                                                                    <i class="ri-delete-bin-fill align-bottom me-2 text-muted"></i>
                                                                    Delete
                                                                </a>
                                                            </li>

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

        <!-- Edit Modal -->
        <div id="zoomInEditModal" class="modal fade zoomIn" tabindex="-1" aria-labelledby="zoomInEditModalLabel" aria-hidden="true" style="display: none;">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="zoomInEditModalLabel">Update Cargo Info</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form method="POST" action="{{ route('cargoInfo.updateData') }}" enctype="multipart/form-data" id="cargoInfoUpdateForm">
                            @csrf
                            <input type="hidden" class="form-control" id="slug" name="slug">
                            <div class="row g-3">
                                <div class="col-xxl-12">
                                    <div class="">
                                        <label for="cargo_id" class="form-label">Cargo <span class="text-danger">*</span></label>
                                        <select class="js-example-basic-single" id="box-cargo-id" name="cargo_id">
                                            <option>Select Cargo</option>
                                            @foreach($cargoListData as $data)
                                                <option value="{{ $data->id }}">{{ $data->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                </div>
                                <div class="col-xxl-6">
                                    <div>
                                        <label for="length" class="form-label">Box Dimension <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="box-dimension" name="box_dimension">
                                    </div>
                                </div>
                                <div class="col-xxl-6">
                                    <div>
                                        <label for="icon_name" class="form-label">Quantity</label>
                                        <input type="text" class="form-control ajax-validation-input" id="box-quantity" name="quantity">
                                    </div>
                                </div>
                                <div class="col-lg-12">
                                    <div class="hstack gap-2 justify-content-end">
                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                                        <button type="submit" class="btn btn-primary ajax-submit">
                                            <div class="submit-btn-text">Update</div>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    {{-- <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary ">Save Changes</button>
                    </div> --}}

                </div>
            </div>
        </div>

        @include('admin.v1.cargo.cargo-info.ajax.index')

    @endsection
