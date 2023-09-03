<!-- JAVASCRIPT -->
<script src="{!! asset('admin/assets/libs/bootstrap/js/bootstrap.bundle.min.js') !!}"></script>
<script src="{!! asset('admin/assets/libs/simplebar/simplebar.min.js') !!}"></script>
<script src="{!! asset('admin/assets/libs/node-waves/waves.min.js') !!}"></script>
<script src="{!! asset('admin/assets/libs/feather-icons/feather.min.js') !!}"></script>
<script src="{!! asset('admin/assets/js/pages/plugins/lord-icon-2.1.0.js') !!}"></script>
<script src="{!! asset('admin/assets/js/plugins.js') !!}"></script>

<!--Jquery-->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- apexcharts -->
<script src="{!! asset('admin/assets/libs/apexcharts/apexcharts.min.js') !!}"></script>

<!-- Vector map-->
<script src="{!! asset('admin/assets/libs/jsvectormap/js/jsvectormap.min.js') !!}"></script>
<script src="{!! asset('admin/assets/libs/jsvectormap/maps/world-merc.js') !!}"></script>

<!--Swiper slider js-->
<script src="{!! asset('admin/assets/libs/swiper/swiper-bundle.min.js') !!}"></script>

<!-- Dashboard init -->
<script src="{!! asset('admin/assets/js/pages/dashboard-ecommerce.init.js') !!}"></script>

<!-- prismjs plugin -->
<script src="{!! asset('admin/assets/libs/prismjs/prism.js') !!}"></script>


<!--datatable js-->
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.print.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>

<script src="{!! asset('admin/assets/js/pages/datatables.init.js') !!}"></script>


<!-- ckeditor -->
{{-- <script src="{!! asset('admin/assets/libs/@ckeditor/ckeditor5-build-classic/build/ckeditor.js') !!}"></script> --}}
<script src="https://cdn.ckeditor.com/ckeditor5/36.0.1/super-build/ckeditor.js"></script>
{{-- <script src="https://cdn.ckeditor.com/ckeditor5/36.0.1/classic/ckeditor.js"></script> --}}
<!--
    Uncomment to load the Spanish translation
    <script src="https://cdn.ckeditor.com/ckeditor5/36.0.1/super-build/translations/es.js"></script>
-->
<script>
    // This sample still does not showcase all CKEditor 5 features (!)
    // Visit https://ckeditor.com/docs/ckeditor5/latest/features/index.html to browse all the features.
    // ClassicEditor.create( document.querySelector( '#ckeditor-classic' ) ).catch(
    //     error => {
    //         console.error( error );
    //     }
    // );
    CKEDITOR.ClassicEditor.create(document.getElementById("ckeditor-classic"), {
        // https://ckeditor.com/docs/ckeditor5/latest/features/toolbar/toolbar.html#extended-toolbar-configuration-format
        toolbar: {
            items: [
                'exportPDF','exportWord', '|',
                'findAndReplace', 'selectAll', '|',
                'heading', '|',
                'bold', 'italic', 'strikethrough', 'underline', 'code', 'subscript', 'superscript', 'removeFormat', '|',
                'bulletedList', 'numberedList', 'todoList', '|',
                'outdent', 'indent', '|',
                'undo', 'redo',
                '-',
                'fontSize', 'fontFamily', 'fontColor', 'fontBackgroundColor', 'highlight', '|',
                'alignment', '|',
                'link', 'insertImage', 'blockQuote', 'insertTable', 'mediaEmbed', 'codeBlock', 'htmlEmbed', '|',
                'specialCharacters', 'horizontalLine', 'pageBreak', '|',
                'textPartLanguage', '|',
                'sourceEditing'
            ],
            shouldNotGroupWhenFull: true
        },
        // Changing the language of the interface requires loading the language file using the <script> tag.
        // language: 'es',
        list: {
            properties: {
                styles: true,
                startIndex: true,
                reversed: true
            }
        },
        // https://ckeditor.com/docs/ckeditor5/latest/features/headings.html#configuration
        heading: {
            options: [
                { model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
                { model: 'heading1', view: 'h1', title: 'Heading 1', class: 'ck-heading_heading1' },
                { model: 'heading2', view: 'h2', title: 'Heading 2', class: 'ck-heading_heading2' },
                { model: 'heading3', view: 'h3', title: 'Heading 3', class: 'ck-heading_heading3' },
                { model: 'heading4', view: 'h4', title: 'Heading 4', class: 'ck-heading_heading4' },
                { model: 'heading5', view: 'h5', title: 'Heading 5', class: 'ck-heading_heading5' },
                { model: 'heading6', view: 'h6', title: 'Heading 6', class: 'ck-heading_heading6' }
            ]
        },
        // https://ckeditor.com/docs/ckeditor5/latest/features/editor-placeholder.html#using-the-editor-configuration
        // placeholder: 'Welcome to CKEditor 5!',
        // https://ckeditor.com/docs/ckeditor5/latest/features/font.html#configuring-the-font-family-feature
        fontFamily: {
            options: [
                'default',
                'Arial, Helvetica, sans-serif',
                'Courier New, Courier, monospace',
                'Georgia, serif',
                'Lucida Sans Unicode, Lucida Grande, sans-serif',
                'Tahoma, Geneva, sans-serif',
                'Times New Roman, Times, serif',
                'Trebuchet MS, Helvetica, sans-serif',
                'Verdana, Geneva, sans-serif'
            ],
            supportAllValues: true
        },
        // https://ckeditor.com/docs/ckeditor5/latest/features/font.html#configuring-the-font-size-feature
        fontSize: {
            options: [ 10, 12, 14, 'default', 18, 20, 22 ],
            supportAllValues: true
        },
        // Be careful with the setting below. It instructs CKEditor to accept ALL HTML markup.
        // https://ckeditor.com/docs/ckeditor5/latest/features/general-html-support.html#enabling-all-html-features
        htmlSupport: {
            allow: [
                {
                    name: /.*/,
                    attributes: true,
                    classes: true,
                    styles: true
                }
            ]
        },
        // Be careful with enabling previews
        // https://ckeditor.com/docs/ckeditor5/latest/features/html-embed.html#content-previews
        htmlEmbed: {
            showPreviews: true
        },
        // https://ckeditor.com/docs/ckeditor5/latest/features/link.html#custom-link-attributes-decorators
        link: {
            decorators: {
                addTargetToExternalLinks: true,
                defaultProtocol: 'https://',
                toggleDownloadable: {
                    mode: 'manual',
                    label: 'Downloadable',
                    attributes: {
                        download: 'file'
                    }
                }
            }
        },
        // https://ckeditor.com/docs/ckeditor5/latest/features/mentions.html#configuration
        mention: {
            feeds: [
                {
                    marker: '@',
                    feed: [
                        '@apple', '@bears', '@brownie', '@cake', '@cake', '@candy', '@canes', '@chocolate', '@cookie', '@cotton', '@cream',
                        '@cupcake', '@danish', '@donut', '@dragée', '@fruitcake', '@gingerbread', '@gummi', '@ice', '@jelly-o',
                        '@liquorice', '@macaroon', '@marzipan', '@oat', '@pie', '@plum', '@pudding', '@sesame', '@snaps', '@soufflé',
                        '@sugar', '@sweet', '@topping', '@wafer'
                    ],
                    minimumCharacters: 1
                }
            ]
        },

        // The "super-build" contains more premium features that require additional configuration, disable them below.
        // Do not turn them on unless you read the documentation and know how to configure them and setup the editor.
        removePlugins: [
            // These two are commercial, but you can try them out without registering to a trial.
            // 'ExportPdf',
            // 'ExportWord',
            'CKBox',
            'CKFinder',
            'EasyImage',
            // This sample uses the Base64UploadAdapter to handle image uploads as it requires no configuration.
            // https://ckeditor.com/docs/ckeditor5/latest/features/images/image-upload/base64-upload-adapter.html
            // Storing images as Base64 is usually a very bad idea.
            // Replace it on production website with other solutions:
            // https://ckeditor.com/docs/ckeditor5/latest/features/images/image-upload/image-upload.html
            // 'Base64UploadAdapter',
            'RealTimeCollaborativeComments',
            'RealTimeCollaborativeTrackChanges',
            'RealTimeCollaborativeRevisionHistory',
            'PresenceList',
            'Comments',
            'TrackChanges',
            'TrackChangesData',
            'RevisionHistory',
            'Pagination',
            'WProofreader',
            // Careful, with the Mathtype plugin CKEditor will not load when loading this sample
            // from a local file system (file://) - load this site via HTTP server if you enable MathType
            'MathType'
        ]
    });
</script>

<!-- dropzone js -->
<script src="{!! asset('admin/assets/libs/dropzone/dropzone-min.js') !!}"></script>

<!-- filepond js -->
<!-- <script src="{!! asset('admin/assets/libs/filepond/filepond.min.js') !!}"></script>
<script src="{!! asset('admin/assets/libs/filepond-plugin-image-preview/filepond-plugin-image-preview.min.js') !!}"></script>
<script src="{!! asset('admin/assets/libs/filepond-plugin-file-validate-size/filepond-plugin-file-validate-size.min.js') !!}"></script>
<script src="{!! asset('admin/assets/libs/filepond-plugin-image-exif-orientation/filepond-plugin-image-exif-orientation.min.js') !!}"></script>
<script src="{!! asset('admin/assets/libs/filepond-plugin-file-encode/filepond-plugin-file-encode.min.js') !!}"></script> -->

<script src="{!! asset('admin/assets/js/pages/ecommerce-product-create.init.js') !!}"></script>

<!--select2 cdn-->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script src="{!! asset('admin/assets/js/pages/select2.init.js') !!}"></script>

<!-- Modern colorpicker bundle -->
<script src="{!! asset('admin/assets/libs/@simonwep/pickr/pickr.min.js') !!}"></script>

<!-- init js -->
<script src="{!! asset('admin/assets/js/pages/form-pickers.init.js') !!}"></script>

<!-- App js -->
<script src="{!! asset('admin/assets/js/app.js') !!}"></script>
