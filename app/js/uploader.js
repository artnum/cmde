function Uploader () {
    if (Uploader.__instance) { return Uploader.__instance }
    
    this.uploads = new Map()
    this.worker = new Worker('js/vendor/kfileupload/client/kfileslicer.js')

    this.worker.postMessage({operation: 'init', url: (new URL('../api/upload.php', window.location)).toString()})
    this.worker.onmessage = event => {
        const message = event.data

        switch(message.operation) {
            case 'state':
                for (const file of message.files) {
                    const upload = this.uploads.get(file.token)
                    if (!upload) { continue }
                    const percent = (file.max - file.left) * 100 / file.max
                    if (upload[3] !== percent) {
                        upload[3] = percent
                        upload[2](percent, file)
                        this.uploads.set(file.token, upload)
                    }
                }
                break
            case 'uploadDone':
                const upload = this.uploads.get(message.content.token)
                upload[0](message.content)
                this.uploads.delete(message.content.token)
                break
        }
        /*
        const [resolve, reject] = this.uploads.get(message.content.token)
        this.uploads.delete(message.content.token)*/

    }
    Uploader.__instance = this
    return this
}

Uploader.prototype.start = function (file, token, progresscb) {
    return new Promise((resolve, reject) => {
        this.worker.postMessage({file, token: token})
        this.uploads.set(token, [resolve, reject, progresscb, 0])
    })
}