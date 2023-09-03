<!DOCTYPE html>
<html data-layout="vertical" data-topbar="light" data-sidebar="dark" data-sidebar-size="lg" data-sidebar-image="none" data-layout-mode="dark" data-body-image="img-3" data-preloader="disable">

    <head>

        <title>Dashboard</title>

        @include('admin.include.title-meta')
        @include('admin.include.head-css')

        <!-- jsvectormap css -->
        <link href="{!! asset('admin/assets/libs/jsvectormap/css/jsvectormap.min.css') !!}" rel="stylesheet" type="text/css" />
        <!--Swiper slider css-->
        <link href="{!! asset('admin/assets/libs/swiper/swiper-bundle.min.css') !!}" rel="stylesheet" type="text/css" />
        <!--sweetalert2 css-->
        <script src="//cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
    	<link rel="stylesheet" href="//cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" id="theme-styles">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/echarts/5.3.1/echarts.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <!--Jquery-->
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        {{-- <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script> --}}


    </head>

    <body>

        <!-- Begin page -->
        <div id="layout-wrapper">

            @include('admin.include.topbar')
            @include('admin.include.sidebar')

            <!-- ============================================================== -->
            <!-- Start right Content here -->
            <!-- ============================================================== -->
            <div class="main-content overflow-hidden">



                @yield('content')


                <footer class="footer border-top">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-sm-6">
                                <script>document.write(new Date().getFullYear())</script> © Mahadi.
                            </div>
                            <div class="col-sm-6">
                                <div class="text-sm-end d-none d-sm-block">
                                    Design & Develop by Mahadi
                                </div>
                            </div>
                        </div>
                    </div>
                </footer>

            </div>
            <!-- end main content-->

        </div>
        <!-- END layout-wrapper -->



        @include('admin.include.customizer')
        @include('admin.include.vendor-scripts')


    </body>

</html>
