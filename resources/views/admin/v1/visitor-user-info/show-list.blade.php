@extends('admin.include.master')
    @section('content')

                <div class="page-content">
                    <div class="container-fluid">

                        <!-- start page title -->
                        <div class="row">
                            <div class="col-12">
                                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                                    <h4 class="mb-sm-0">Visited Users</h4>
                                    <div class="page-title-right">
                                        <ol class="breadcrumb m-0">
                                            <li class="breadcrumb-item"><a href="javascript: void(0);">Visited Users</a></li>
                                            <li class="breadcrumb-item active">Lists</li>
                                        </ol>
                                    </div>

                                </div>
                            </div>
                        </div>
                        <!-- end page title -->


                        <div class="row">
                            <div class="col-lg-12">


                                <div class="card">

                                    <div class="card-header border-0">
                                        <div class="row g-4">
                                            
                                            
                                        </div>
                                    </div>

                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Visited User Lists</h5>
                                    </div>

                                    <div class="card-body">
                                        <!-- <table id="example" class="table table-bordered dt-responsive nowrap table-striped align-middle" style="width:100%"> -->
                                        <table id="buttons-datatables" class="display table table-bordered" style="width:100%">
                                            <thead>
                                                <tr>
                                                    <th>No</th>
                                                    <th>IP</th>
                                                    <th>Country</th>
                                                    <th>Country Code</th>
                                                    <th>City</th>
                                                    <th>Zip Code</th>
                                                    <th>Area Code</th>
                                                    <th>Latitude</th>
                                                    <th>Longitude</th>
                                                    <th>Entry Time</th>
                                                </tr>
                                            </thead>
                                            <tbody>

                                               
                                                @foreach($visitorInfo as $key => $data)

                                                    <tr>
                                                        <td>{{ ++$key }}</td>
                                                        <td>{{ $data->ip }}</td>
                                                        <td>{{ $data->countryName }}</td>
                                                        <td>{{ $data->countryCode }}</td>
                                                        <td>{{ $data->cityName }}</td>
                                                        <td>{{ $data->zipCode }}</td>
                                                        <td>{{ $data->areaCode }}</td>
                                                        <td>{{ $data->latitude }}</td>
                                                        <td>{{ $data->longitude }}</td>
                                                        <td>{{ $data->created_at }}</td>
                                                        
                                                    </tr>

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

    @endsection