<div>
    <div x-data="{ 
        open: false,
        scanner: null
    }" 
    x-on:toggle-scanner.window="
        open = !open;
        if (open) {
            $nextTick(() => {
                scanner = new Html5Qrcode('reader');
                scanner.start(
                    { facingMode: 'environment' },
                    {
                        fps: 30,
                        qrbox: { width: 450, height: 450 }
                    },
                    (decodedText) => {
                        $wire.dispatch('scanResult', { decodedText: decodedText });
                        scanner.stop();
                        open = false;
                    },
                    (error) => console.warn(error)
                );
            });
        } else if (scanner) {
            scanner.stop();
        }
    "
    x-show="open"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg max-w-lg w-full relative">
            <h2 class="text-lg font-semibold mb-4">QR Code Scanner</h2>
            <div id="reader" class="w-full"></div>
            <button @click="open = false; if (scanner) scanner.stop();" 
                    class="absolute top-0 right-0 m-2 text-gray-600 hover:text-gray-900 dark:text-gray-400">
                &times;
            </button>
        </div>
    </div>
</div>

{{-- <div x-data="{ open: false }"
     x-on:toggle-scanner.window="open = !open; if(open) { startScanner(); } else { stopScanner(); }"
     x-show="open"
     class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg max-w-lg w-full relative">
        <h2 class="text-lg font-semibold mb-4">Barcode Scanner</h2>
        <div id="scanner" class="w-full h-64 bg-black"></div>
        <button @click="open = false; stopScanner();" class="absolute top-0 right-0 m-2 text-gray-600 dark:text-gray-400">
            &times;
        </button>
    </div>
</div>

<script>
    let scannerStarted = false;

    function startScanner() {
        if (scannerStarted) return;

        Quagga.init({
            inputStream: {
                name: "Live",
                type: "LiveStream",
                target: document.querySelector('#scanner'),
                constraints: {
                    facingMode: "environment"
                }
            },
            decoder: {
                readers: [
                    "code_128_reader",
                    "ean_reader",
                    "ean_8_reader",
                    "upc_reader",
                    "code_39_reader"
                ]
            }
        }, function (err) {
            if (err) {
                console.error(err);
                return;
            }
            Quagga.start();
            scannerStarted = true;

            // ✅ TAMBAHKAN onProcessed DI SINI
            Quagga.onProcessed(function (result) {
                const drawingCtx = Quagga.canvas.ctx.overlay,
                      drawingCanvas = Quagga.canvas.dom.overlay;

                if (result) {
                    if (result.boxes) {
                        drawingCtx.clearRect(0, 0, parseInt(drawingCanvas.getAttribute("width")), parseInt(drawingCanvas.getAttribute("height")));
                        result.boxes.filter(function (box) {
                            return box !== result.box;
                        }).forEach(function (box) {
                            Quagga.ImageDebug.drawPath(box, { x: 0, y: 1 }, drawingCtx, {
                                color: "green",
                                lineWidth: 2
                            });
                        });
                    }

                    if (result.box) {
                        Quagga.ImageDebug.drawPath(result.box, { x: 0, y: 1 }, drawingCtx, {
                            color: "#00F",
                            lineWidth: 2
                        });
                    }

                    if (result.codeResult && result.codeResult.code) {
                        console.log("Code processed: ", result.codeResult.code);
                    }
                }
            });

            // ✅ TAMBAHKAN onDetected
            Quagga.onDetected(function (data) {
                const code = data.codeResult.code;
                console.log("✅ Barcode detected:", code);
                window.livewire.emit('scanResult', code);
                stopScanner();
            });
        });
    }

    function stopScanner() {
        if (scannerStarted) {
            Quagga.stop();
            Quagga.offDetected();
            Quagga.offProcessed(); // matikan juga proses highlight kotak
            scannerStarted = false;
        }
    }
</script> --}}
