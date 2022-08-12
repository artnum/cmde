function Index() {
    this.domNode = document.createElement('DIV')
}

Index.prototype.render = function () {
    return new Promise((resolve, reject) => {
        resolve(['CMDE', this.domNode])
    })
}