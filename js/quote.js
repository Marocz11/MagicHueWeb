let filesArray = [];

    // Get drop zone and file input
    const dropZone = document.getElementById('drop-zone');
    const fileInput = document.getElementById('file-input');
    const fileList = document.getElementById('file-list');

    // Event listeners for drag and drop
    dropZone.addEventListener('click', () => fileInput.click());

    dropZone.addEventListener('dragover', (event) => {
        event.preventDefault();
        dropZone.classList.add('dragover');
    });

    dropZone.addEventListener('dragleave', () => {
        dropZone.classList.remove('dragover');
    });

    dropZone.addEventListener('drop', (event) => {
        event.preventDefault();
        dropZone.classList.remove('dragover');
        handleFiles(event.dataTransfer.files);
    });

    fileInput.addEventListener('change', () => handleFiles(fileInput.files));

    // Handle adding files
    function handleFiles(newFiles) {
        for (let i = 0; i < newFiles.length; i++) {
            filesArray.push(newFiles[i]);
        }
        updateFileInput();
        displayFileNames();
    }

    // Update the file list display
    function displayFileNames() {
        fileList.innerHTML = '';
        filesArray.forEach((file, index) => {
            const fileItem = document.createElement('div');
            fileItem.classList.add('file-item');
            fileItem.innerHTML = `
                <span>${file.name}</span>
                <button onclick="removeFile(${index})">Smazat</button>
            `;
            fileList.appendChild(fileItem);
        });
    }

    // Remove file from the list
    function removeFile(index) {
        filesArray.splice(index, 1);
        updateFileInput();
        displayFileNames();
    }

    // Update the hidden file input to include files from the array
    function updateFileInput() {
        const dataTransfer = new DataTransfer(); // Create a new DataTransfer object

        filesArray.forEach((file) => {
            dataTransfer.items.add(file); // Add each file to the DataTransfer object
        });

        fileInput.files = dataTransfer.files; // Set the input element's files property to the DataTransfer files
    }