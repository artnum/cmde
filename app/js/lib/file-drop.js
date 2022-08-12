function FileDrop (node) {
    this.showFileNode = node

    this.mimetypes = {
        'application/msword': 'word',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document': 'word',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.template': 'word',
        'application/vnd.ms-word.document.macroEnabled.12': 'word',
        'application/vnd.ms-word.template.macroEnabled.12': 'word',
        'application/vnd.ms-excel': 'excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': 'excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.template': 'excel',
        'application/vnd.ms-excel.sheet.macroEnabled.12': 'excel',
        'application/vnd.ms-excel.template.macroEnabled.12': 'excel',
        'application/vnd.ms-excel.addin.macroEnabled.12': 'excel',
        'application/vnd.ms-excel.sheet.binary.macroEnabled.12': 'excel',
        'application/vnd.ms-powerpoint': 'powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation': 'powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.template': 'powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.slideshow': 'powerpoint',
        'application/vnd.ms-powerpoint.addin.macroEnabled.12': 'powerpoint',
        'application/vnd.ms-powerpoint.presentation.macroEnabled.12': 'powerpoint',
        'application/vnd.ms-powerpoint.template.macroEnabled.12': 'powerpoint',
        'application/vnd.ms-powerpoint.slideshow.macroEnabled.12': 'powerpoint',
        'application/vnd.ms-access': 'database',
        'application/pdf': 'pdf',
        'application/vnd.oasis.opendocument.text': 'word',
        'application/vnd.oasis.opendocument.text-template': 'word',
        'application/vnd.oasis.opendocument.text-web': 'html',
        'application/vnd.oasis.opendocument.presentation': 'powerpoint',
        'application/vnd.oasis.opendocument.presentation-template': 'powerpoint',
        'application/vnd.oasis.opendocument.spreadsheet': 'excel',
        'application/vnd.oasis.opendocument.spreadsheet-template': 'excel',
        'application/vnd.sun.xml.writer': 'word',
        'application/vnd.sun.xml.writer.template': 'word',
        'application/vnd.sun.xml.writer.global': 'word',
        'application/vnd.stardivision.writer': 'word',
        'application/vnd.stardivision.writer-global': 'word',
        'application/vnd.sun.xml.calc': 'excel',
        'application/vnd.sun.xml.calc.template': 'excel',
        'application/vnd.stardivision.calc': 'excel',
        'application/vnd.sun.xml.impress': 'powerpoint',
        'application/vnd.sun.xml.impress.template': 'powerpoint',
        'application/vnd.stardivision.impress': 'powerpoint',
        'text/html': 'html',
        'application/xhtml+xml': 'html',
        'application/xml': 'html',
        'text/xml': 'html',
        'application/atom+xml': 'html',
        'application/vnd.mozilla.xul+xml': 'html',
        'application/gzip': 'compress',
        'application/x-bzip': 'compress',
        'application/x-bzip2': 'compress',
        'application/vnd.rar': 'compress',
        'application/x-tar': 'compress',
        'application/zip': 'compress',
        'application/x-7z-compressed': 'compress',
        'application/rtf': 'word',
        'application/json': 'text',
        'application/ld+json': 'text',
        'application/java-archive': 'compress',
        'text/calendar': 'calendar'
    }

}

FileDrop.prototype.upload = function (file, commandeUid, progressUid) {
    krequest(new URL('../api/fake-auth.php', window.location))
    .then(response => {

        if (response.body.token) {
            const div = document.createElement('DIV')
            div.innerHTML = '<div class="progress"></div><div class="name"> ... </div>'
            div.id = `file-${response.body.token}`
            div.classList.add('file-image')
            if (this.mimetypes[file.type]) {
                div.classList.add(this.mimetypes[file.type])
            } else {
                if (file.type === '') {
                    div.classList.add('binary')     
                } else {
                    div.classList.add(file.type.split('/')[0])
                }
            }
            window.requestAnimationFrame(() => {
                this.showFileNode.appendChild(div)
            })
            const uploader = new Uploader()
            uploader.start(file, response.body.token, this.progress.bind(this))
            .then(file => {
                const node = this.showFileNode.querySelector(`#file-${file.token}`)
                window.requestAnimationFrame(() => {
                    node.innerHTML = `<div class="progress"></div><div class="name">${file.filename}</div>`
                    node.classList.add('uploaded')
                })
            })
            .catch(data => {

            })

        }
    })
}

FileDrop.prototype.progress = function (percent, file) {
    const node = this.showFileNode.querySelector(`#file-${file.token}`)
    window.requestAnimationFrame(() => {
        node.innerHTML = `<div class="progress" style="width: ${50 * percent / 100}px"></div><div class="name">${Math.trunc(percent, 1)}%</div>`
    })
}
