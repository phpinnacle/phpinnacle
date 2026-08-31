<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <title>{{ $document->number }}</title>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        @vite('resources/css/paged.css')
    </head>
    <body>
        <main id="print-content" >
            {!! $document->content !!}
        </main>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
        <script>
            window.addEventListener('load', () => {
                const element = document.getElementById('print-content');

                html2pdf().from(element).set({
                    margin:       10,
                    image:        { type: 'jpeg', quality: 0.98 },
                    html2canvas:  { scale: 2, useCORS: true },
                    jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
                });
            });
        </script>
    </body>
</html>
