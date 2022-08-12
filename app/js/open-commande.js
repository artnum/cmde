function ListCommande(type = 'opened') {
    this.type = type
    this.title = 'Commandes ouvertes'
}

function OpenedCommande () {
    return new ListCommande('opened')

}

function DeletedCommande () {
    const list = new ListCommande('deleted')
    list.title = 'Commandes supprimées'
    return list
}

function ClosedCommande () {
    const list = new ListCommande('closed')
    list.title = 'Commandes fermées'
    return list
}

ListCommande.prototype.render = function () {
    return new Promise(resolve => {
        krequest(new URL(`../api/commande/${this.type}`, window.location))
        .then(response =>{ 
            if (response.body.length < 1) { return resolve([this.title, document.createElement('DIV')]) }

            const node = document.createElement('DIV')
            for (const element of response.body.data) {
                node.innerHTML += `
                    <div id="commande_${element.uid}">
                        <span>${element.reference}</span>
                        <span>${element.altreference}</span>
                        <span>${element._relation.ptype.name}</span>
                    </div>
                `
            }
            node.addEventListener('click', event => {
                let node = event.target
                if (!node.id) {
                    while (node && !node.id) { node = node.parentNode }
                }
                if (!node) { return }
                console.log(node.id)
                krequest(new URL(`../api/commande/${node.id.split('_')[1]}`, window.location), {method: 'DELETE'})
                .then(_ => {
                    window.requestAnimationFrame(() => {
                        node.parentNode.removeChild(node)
                    })
                })
            }, {passive: true})

            resolve([this.title,node])
        })
    })
}