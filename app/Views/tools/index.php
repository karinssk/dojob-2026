<div id="page-content" class="page-wrapper clearfix">
    <div class="card clearfix">
        <div class="card-body">
            <h4 class="mb15">Video Downloader</h4>

            <div class="row">
                <div class="col-md-8">
                    <label for="tools-url" class="form-label">Video URL</label>
                    <input id="tools-url" type="text" class="form-control" placeholder="Paste a video URL here">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Actions</label>
                    <div class="d-flex gap-2">
                        <button id="tools-preview-btn" class="btn btn-default">Preview</button>
                        <button id="tools-download-btn" class="btn btn-primary">Download MP4</button>
                    </div>
                </div>
            </div>

            <div id="tools-preview" class="mt20 hide">
                <div class="row">
                    <div class="col-md-3">
                        <img id="tools-thumb" src="" alt="thumbnail" class="img-fluid rounded">
                    </div>
                    <div class="col-md-9">
                        <div><strong>Title:</strong> <span id="tools-title"></span></div>
                        <div><strong>Source:</strong> <span id="tools-extractor"></span></div>
                        <div><strong>Duration:</strong> <span id="tools-duration"></span></div>
                    </div>
                </div>
            </div>

            <div id="tools-status" class="mt15 text-muted"></div>
            <div id="tools-download-link" class="mt10 hide">
                <a id="tools-file-link" href="#" class="btn btn-success" target="_blank">Download File</a>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        var $url = $("#tools-url");
        var $status = $("#tools-status");
        var $preview = $("#tools-preview");
        var $thumb = $("#tools-thumb");
        var $title = $("#tools-title");
        var $extractor = $("#tools-extractor");
        var $duration = $("#tools-duration");
        var $downloadLink = $("#tools-download-link");
        var $fileLink = $("#tools-file-link");

        function setStatus(text, isError) {
            $status.text(text || "");
            $status.toggleClass("text-danger", !!isError);
        }

        function getCsrfData() {
            var data = {};
            if (window.AppHelper && AppHelper.csrfTokenName && AppHelper.csrfHash) {
                data[AppHelper.csrfTokenName] = AppHelper.csrfHash;
            }
            return data;
        }

        $("#tools-preview-btn").on("click", function () {
            var url = $.trim($url.val());
            if (!url) {
                setStatus("Please enter a URL.", true);
                return;
            }

            setStatus("Loading preview...");
            $preview.addClass("hide");
            $downloadLink.addClass("hide");

            $.ajax({
                url: "<?php echo get_uri('tools/preview'); ?>",
                type: "POST",
                dataType: "json",
                data: $.extend({url: url}, getCsrfData()),
                success: function (res) {
                    if (!res || !res.success) {
                        setStatus(res && res.message ? res.message : "Preview failed.", true);
                        return;
                    }
                    $thumb.attr("src", res.thumbnail || "");
                    $title.text(res.title || "-");
                    $extractor.text(res.extractor || "-");
                    $duration.text(res.duration ? (res.duration + " sec") : "-");
                    $preview.removeClass("hide");
                    setStatus("Preview ready.");
                },
                error: function () {
                    setStatus("Preview request failed.", true);
                }
            });
        });

        $("#tools-download-btn").on("click", function () {
            var url = $.trim($url.val());
            if (!url) {
                setStatus("Please enter a URL.", true);
                return;
            }

            setStatus("Downloading... please wait.");
            $downloadLink.addClass("hide");

            $.ajax({
                url: "<?php echo get_uri('tools/download'); ?>",
                type: "POST",
                dataType: "json",
                data: $.extend({url: url, title: $title.text()}, getCsrfData()),
                success: function (res) {
                    if (!res || !res.success) {
                        setStatus(res && res.message ? res.message : "Download failed.", true);
                        return;
                    }
                    $fileLink.attr("href", res.file_url);
                    $downloadLink.removeClass("hide");
                    setStatus("Download ready.");
                },
                error: function () {
                    setStatus("Download request failed.", true);
                }
            });
        });
    })();
</script>
