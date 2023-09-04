@extends('admin.include.master')
    @section('content')

        <div class="page-content">
            <div class="container-fluid">

                <!-- start page title -->
                <div class="row">
                    <div class="col-12">
                        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                            <h4 class="mb-sm-0">Distribute Cargo</h4>
                            <div class="page-title-right">
                                <ol class="breadcrumb m-0">
                                    <li class="breadcrumb-item"><a href="javascript: void(0);">Distribute Cargo</a></li>
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

                    <form method="POST" action="{{ route('distributeCargo.get.optimizedData') }}" enctype="multipart/form-data">

                        @csrf

                        <div class="row">
                            <div class="col-lg-12">

                                <div class="card">

                                    <div class="card-header align-items-center d-flex">
                                        <h4 class="card-title mb-0 flex-grow-1">Distribute Cargo</h4>

                                    </div><!-- end card header -->

                                    <div class="card-body">

                                        <div class="row">

                                            <div class="col-md-12">
                                                <div class="mb-3">
                                                    <select name="cargo_id" id="cargo_id" class="js-example-basic-single cargo_id">
                                                        <option>Select Cargo</option>
                                                        @foreach($cargoListData as $data)
                                                            <option value="{{ $data->id }}">{{ $data->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                            </div><!--end col-->

                                            <div class="col-md-12 hide" id="available-box-info">

                                                <div class="card">

                                                    <div class="card-header">
                                                        <h5 class="card-title mb-0">Available Box Info</h5>
                                                    </div>

                                                    <div class="card-body">
                                                        <table id="buttons-datatables" class="table table-bordered dt-responsive nowrap table-striped align-middle" style="width:100%">
                                                            <thead>
                                                                <tr>
                                                                    <th>No</th>
                                                                    <th>Dimension</th>
                                                                    <th>Quantity</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>

                                                            </tbody>
                                                        </table>

                                                    </div>
                                                </div>
                                            </div>

                                            {{-- <a class="btn btn-success" href="{{ route('distributeCargo.fetch.optimizedData', 1) }}">
                                                <i class="ri-file-list-3-line"></i> <span>Cargo Info</span>
                                            </a> --}}

                                        </div>

                                    </div>
                                </div>
                                <!-- end card -->

                                <div class="text-end mb-3">
                                    <button type="submit" class="btn btn-success w-sm">Get Result</button>
                                </div>
                            </div>
                            <!-- end col -->


                        </div>
                        <!-- end row -->

                    </form>

                </div>

                <div class="row">
                    <div class="col-lg-12">
                    </div>
                </div>

            </div>
            <!-- container-fluid -->
        </div>
        <!-- End Page-content -->

        @include("admin.v1.cargo.distribute-cargo.ajax.index")

    @endsection