<!DOCTYPE html>
<html lang="en">

    <head>

        @include('frontend.layouts.title-meta')

        @include('frontend.layouts.head-css')

        <!--Jquery-->
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    </head>

    <body>

        <div class="preloader">
            <img class="preloader__image" width="60" src="{{asset('frontend/assets/images/loader.gif')}}" alt="" />
        </div>
        <!-- /.preloader -->

        <div class="page-wrapper">

            @include('frontend.layouts.topbar')


            <!-- ============================================================== -->
            <!-- Start right Content here -->
            <!-- ============================================================== -->

            @yield('content')


            @include('frontend.layouts.footer')


        </div><!-- /.page-wrapper -->


        <div class="mobile-nav__wrapper">
            <div class="mobile-nav__overlay mobile-nav__toggler"></div>
            <!-- /.mobile-nav__overlay -->
            <div class="mobile-nav__content">
                <span class="mobile-nav__close mobile-nav__toggler"><i class="fa fa-times"></i></span>

                <div class="logo-box">
                    <a href="{{ route('home') }}" aria-label="logo image"><img src="{{asset('frontend/assets/images/resources/logo.png')}}" width="155"
                            alt="" /></a>
                </div>
                <!-- /.logo-box -->
                <div class="mobile-nav__container"></div>
                <!-- /.mobile-nav__container -->

                <ul class="mobile-nav__contact list-unstyled">
                    <li>
                        <i class="fa fa-envelope"></i>
                        <a href="mailto:needhelp@packageName__.com">needhelp@tevily.com</a>
                    </li>
                    <li>
                        <i class="fa fa-phone-alt"></i>
                        <a href="tel:666-888-0000">666 888 0000</a>
                    </li>
                </ul><!-- /.mobile-nav__contact -->
                <div class="mobile-nav__top">
                    <div class="mobile-nav__social">
                        <a href="#" class="fab fa-twitter"></a>
                        <a href="#" class="fab fa-facebook-square"></a>
                        <a href="#" class="fab fa-instagram"></a>
                    </div><!-- /.mobile-nav__social -->
                </div><!-- /.mobile-nav__top -->



            </div>
            <!-- /.mobile-nav__content -->
        </div>
        <!-- /.mobile-nav__wrapper -->

        {{-- <div class="search-popup">
            <div class="search-popup__overlay search-toggler"></div>

            <div class="search-popup__content">
                <form action="#">
                    <label for="search" class="sr-only">search here</label><!-- /.sr-only -->
                    <input type="text" id="search" placeholder="Search Here..." />
                    <button type="submit" aria-label="search submit" class="thm-btn">
                        <i class="icon-magnifying-glass"></i>
                    </button>
                </form>
            </div>

        </div> --}}
        <!-- /.search-popup -->


        @include('frontend.layouts.vendor-scripts')


    </body>

</html>
