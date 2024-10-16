jQuery(document).ready(function ($) {
    let offset = 0;
    const totalFiles = batchScanData.totalFiles; // The count of all the files
    // String used in messages for the user
    const xOfYFilesScanned = batchScanData.localizedStrings.filesScanned;
    const scanFinishedXInfect = batchScanData.localizedStrings.scanFinished;
    const scanError = batchScanData.localizedStrings.scanError;

    // Function to replace placeholders with values
    function sprintf(str) {
        var args = Array.prototype.slice.call(arguments, 1);
        var i = 0;
        return str.replace(/%(\d+\$)?s/g, function (match) {
            return args[i++];
        });
    }

    // Update progress bar
    function updateProgressBar(processedFiles) {
        var progressPercentage = (processedFiles / totalFiles) * 100;
        $('#progress-bar').css('width', progressPercentage + '%');
        $('#progress-bar').text(Math.round(progressPercentage) + '%');
        $('#progress-text').text(sprintf(xOfYFilesScanned, processedFiles, totalFiles));
    }

    let infectedFiles = [];
    // Function to scan the next batch
    function scanNextBatch() {
        $.post(batchScanData.ajaxUrl, {
            action: 'batchScan',
            offset: offset,
            security: batchScanData.nonce,
            infectedFiles: infectedFiles
        }, function (response) {
            if (response.success) {
                offset = response.data.offset || 0;

                // Update progress
                updateProgressBar(response.data.processedFiles);
                infectedFiles = response.data.infectedFiles;
                if (response.data.finished) {
                    const infected = response.data.infectedFiles.length;
                    console.error(response.data.infectedFiles);
                    alert(sprintf(scanFinishedXInfect, infected));
                } else {
                    // Scan next batch
                    scanNextBatch();
                }
            } else {
                alert(scanError);
            }
        });
    }

    // Eventlistener for the start button
    $('#start-scan').on('click', function () {
        offset = 0; // Den Offset zurücksetzen
        updateProgressBar(0); // Fortschritt zurücksetzen
        scanNextBatch(); // Scan starten
    });
});
