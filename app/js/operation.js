function OperationLog() {
    if (OperationLog._instance) { return OperationLog._instance }
    this.domNode = document.getElementById('operations')

    OperationLog._instance = this
}

OperationLog.prototype.add = function (message) {
    if (!(message instanceof Operation)) { message = new Operation(message) }

    const node = document.createElement('DIV')
    node.classList.add('operation')
    let error = ''
    if (message.details.error) { 
        node.classList.add('error') 
        error = error instanceof Error ? `[${error.name}] ${error.message}` : error
    }
    if (!message.details.uid) { message.details.uid = '' }
    node.innerHTML = `
        <span class="id">Operation ${message.id}</span>
        <span class="type">${message.details.type.substring(0, 16)}</span>
        <span class="uid">${message.details.uid}</span>
        <span class="message">${message.message}</span>
        <span class="error">${error}</span>
    `

    window.requestAnimationFrame(() => { this.domNode.insertBefore(node, this.domNode.firstElementChild) })
}

function Operation (message, details = {}) {
    if (!Operation._localid) { Operation._localid = 0 }

    details = Object.assign({error: null, uid: null, type: ''}, details)
    this.message = message
    this.details = details
    this.now = new Date()
    this.id = ++Operation._localid
}


Operation.log = function (message, details = {}) {
    const op = new Operation(message, details)
    const log = new OperationLog()
    log.add(op)
}