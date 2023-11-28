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
                                                        <option selected value="">Select Cargo</option>
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

                @if(session('finalTrucksData'))
                    <div class="row">
                        <div class="col-lg-12">
                            <div>
                                <h5>Selected Trucks:</h5>
                                <div class="timeline">
                                    @foreach (session('finalTrucksData') as $key => $trucksData)
                                            <div class="timeline-item @if (++$key % 2 != 0) left @else right @endif">
                                                <i class="icon ri-truck-line"></i>
                                                {{-- <div class="date">15 Dec 2021</div> --}}
                                                <div class="content">
                                                    <h5 class="pb-2">{{ $trucksData['truck'] }}</h5>
                                                    <p>Number of Trucks: <span class="text-muted">{{ $trucksData['total_truck'] }}</span></p>
                                                    <p>Truck Dimension: <span class="text-muted">{{ $trucksData['truck_dimension'] }}</span></p>
                                                    <p>Box Dimension: <span class="text-muted">{{ $trucksData['box_dimension'] }}</span></p>
                                                    <div class="list-group col nested-list nested-sortable-handle">
                                                        @foreach ($trucksData['individual_truck'] as $key => $emptyTruck)
                                                            <div class="list-group-item "><i class="ri-truck-line align-bottom handle"></i>Truck {{ $key + 1 }}
                                                                <div class="list-group nested-list nested-sortable-handle">
                                                                    <div class="list-group-item"><i class="ri-drag-move-fill align-bottom handle"></i>Full Empty Space
                                                                        <div class="list-group-item"><i class="bx bx-box align-bottom handle"></i>Box Dimension: {{ $emptyTruck['box_dimension'] }}
                                                                            <div class="list-group nested-list nested-sortable-handle">
                                                                                <div class="list-group-item "><i class="ri-drag-move-fill align-bottom handle"></i>Total Box: {{ $emptyTruck['total_box_quantity'] }}</div>
                                                                                <div class="list-group-item"><i class="ri-drag-move-fill align-bottom handle"></i>Filled Box: {{ $emptyTruck['total_filled_box_quantity'] }}</div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="list-group-item"><i class="ri-drag-move-fill align-bottom handle"></i>Partial Empty Space
                                                                        @if ($trucksData['other_box_load_info'][$key]['total_filled_box_quantity'] != null)
                                                                            <div class="list-group-item"><i class="bx bx-box align-bottom handle"></i>Box Dimension: {{ $trucksData['other_box_load_info'][$key]['box_dimension'] }}
                                                                                <div class="list-group nested-list nested-sortable-handle">
                                                                                    <div class="list-group-item "><i class="ri-drag-move-fill align-bottom handle"></i>Total Box: {{ $trucksData['other_box_load_info'][$key]['total_box_quantity'] }}</div>
                                                                                    <div class="list-group-item"><i class="ri-drag-move-fill align-bottom handle"></i>Filled Box: {{ $trucksData['other_box_load_info'][$key]['total_filled_box_quantity'] }}</div>
                                                                                </div>
                                                                            </div>
                                                                        @else
                                                                            <div class="list-group-item" style="color: red">
                                                                                <i class="bx bx-box align-bottom handle"></i>Not Fillable
                                                                            </div>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                    <div class="row g-2">
                                                        <div class="col-sm-6">
                                                            <button type="submit" class="btn btn-success w-sm">View More</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                        {{-- {{ $key }}: {{ $trucksData }}<br> --}}
                                    @endforeach
                                </div>
                            </div>
                        </div><!--end col-->
                    </div><!--end row-->
                @endif

            </div>
            <!-- container-fluid -->
        </div>
        <!-- End Page-content -->

        @include("admin.v1.cargo.distribute-cargo.ajax.index")

    @endsection
