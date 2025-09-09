<!DOCTYPE html>
<html>
<head>
    <title>Multipart Upload to S3 via Blade</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
    <h2>Upload Large File to S3</h2>

    <input type="file" id="fileInput">
    <button onclick="startUpload()">Upload</button>

    <div id="status"></div>

    <script>
        const API_BASE = "{{ url('/') }}";

        async function startUpload() {
            const fileInput = document.getElementById('fileInput');
            const file = fileInput.files[0];
            if (!file) return alert('Please select a file');

            const partSize = 5 * 1024 * 1024;
            const totalParts = Math.ceil(file.size / partSize);
            const parts = [...Array(totalParts)].map((_, i) => i + 1);

            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            // 1. Initiate Upload
            const initRes = await fetch(`${API_BASE}/api/initiate-upload`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    fileName: file.name,
                    fileType: file.type,
                    fileSize: file.size,
                }),
            });

            const initData = await initRes.json();
            const { uploadId, key, partSize: serverPartSize } = initData;

            // 2. Get Presigned URLs
            const presignedRes = await fetch(`${API_BASE}/api/get-presigned-urls`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    uploadId,
                    key,
                    parts,
                }),
            });

            const { urls } = await presignedRes.json();
            const uploadedParts = [];

            // 3. Upload each part to S3
            for (let i = 0; i < parts.length; i++) {
                const start = i * partSize;
                const end = Math.min(start + partSize, file.size);
                const blobPart = file.slice(start, end);

                const partNumber = parts[i];
                const url = urls.find(p => p.partNumber === partNumber).url;

                const uploadRes = await fetch(url, {
                    method: 'PUT',
                    body: blobPart,
                });

                if (!uploadRes.ok) {
                    alert(`Upload failed for part ${partNumber}`);
                    return;
                }

                const ETag = uploadRes.headers.get('ETag').replace(/"/g, '');
                uploadedParts.push({ PartNumber: partNumber, ETag });

                document.getElementById('status').innerText = `Uploaded part ${partNumber}/${parts.length}`;
            }

            // 4. Complete Upload
            const completeRes = await fetch(`${API_BASE}/api/complete-upload`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    uploadId,
                    key,
                    parts: uploadedParts,
                }),
            });

            const completeData = await completeRes.json();

            document.getElementById('status').innerText = `✅ Upload completed. File URL: ${completeData.location}`;
        }
    </script>
</body>
</html>
