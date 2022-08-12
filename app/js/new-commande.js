function NewCommande(update = false) {
    console.log('run new commande')
    this.domNode = document.createElement('DIV')
    this.update = update
}

NewCommande.prototype.create = function () {
    return new Promise((resolve) => {
        const url = new URL(`../api/commande`, window.location)
        krequest(new URL(`../api/commande`, window.location), {method: 'POST', body: {uid: ''}})
        .then(response => {
            if (response.body.length !== 1) { throw new Error('Erreur') }
            const uid = response.body.data[0].uid
            const rid = (new RID()).generate(uid)
            krequest(new URL(`../api/commande/${uid}`, window.location), {method: 'PUT', body: {reference: rid}})
            .then(_ => {
                Operation.log('Commande créé', {uid: rid, type: 'commande'})
                resolve([rid, uid])
            })
        })
        .catch(error => {
            Operation.log('Erreur création commande', {error, type: 'commande'})
            console.log(error)
        })
    })
}

NewCommande.prototype.render = function() {
    return new Promise((resolve, reject) => {
        this.create()
        .then(([reference, uid]) => {
            this.domNode.innerHTML = `
                <form>
                    <input type="hidden" name="commande:uid" value="${uid}" />
                    <label>Référence : <input readonly="readonly" value="${reference}" name="commande:reference" /></label><br>
                    <label>Référence personnelle : <input style="text-transform: uppercase;" value="" name="commande:altreference" /></label><br>
                    <label>Description : <textarea name="commande:description"></textarea></label><br>
                    <div class="filedropper"><span>Déposer fichiers</span></div>
                    <div class="fileupload"></div>
                    <hr />
                    <div class="fournisseur"></div>
                    <hr />
                    <h3>Pièces</h3>
                    <div><span>Position 1</span><input type="number" name="quantity" /><input type="number" name="price" /><input type="text" name="name" /><input type="text" name="reference" /></div>
                    <button type="submit">Sauver</button> <button type="reset">Annuler</button>
                </form>
            `

            const datasource = {
                query (value) {
                    return new Promise((resolve, reject) => {
                        const request = KQueryExpr.fromString(value, {attribute: ['name', ['mobile', 'phone'], ['telephonenumber', 'phone'], ['homephone', 'phone']]})
                        console.log(request)
                        krequest(new URL('../../horaire/Contact/_query', window.location), {method: 'POST', body: request.object()})
                        .then(result => {
                            if (result.body.length < 1) { return resolve([]) }
                            return resolve(result.body.data)
                        })
                        .catch(error => {
                            reject(new Error('Erreur réseau', {cause: error}))
                        })
                    })
                }
            }
            const kcalc = new KCalcSelector(datasource, {'_id': 'uid', 'o': 'Société', 'sn': 'Nom', 'givenname': 'Prénom', 'locality': 'Localité', "mobile|homephone|telephonenumber": 'Téléphone', 'mail': 'Email'})
            kcalc.render()
            .then(kdom => {
                console.log(kdom)
                this.domNode.querySelector('.fournisseur').appendChild(kdom)
            })

            function setDropTarget (event) {
                window.requestAnimationFrame(() => { event.target.classList.add('dragover') })
                event.preventDefault()
                return false
            }
            const filedropper = this.domNode.querySelector('div.filedropper')
            const fileupload = this.domNode.querySelector('div.fileupload')
            filedropper.addEventListener('dragover', event => {
                return setDropTarget(event)
            })
            filedropper.addEventListener('dragenter', event => {
                return setDropTarget(event)
            })
            filedropper.addEventListener('dragleave', event => {
                window.requestAnimationFrame(() => { event.target.classList.remove('dragover') })
            }, {passive: true})
            filedropper.addEventListener('drop', event => {
                const fileDrop = new FileDrop(fileupload)
                window.requestAnimationFrame(() => { event.target.classList.remove('dragover') })
                event.preventDefault()
                console.log(event.dataTransfer.items)
                for (const item of event.dataTransfer.items) {
                    if (item.kind === 'file') {
                        fileDrop.upload(item.getAsFile())
                    }
                }
            })

            this.domNode.addEventListener('submit', event => {
                event.preventDefault()
                const form = new FormData(event.target)
                const body = {}

                for (let [k, v] of form.entries()) {
                    if (k === 'commande:altreference') { v = String(v).toUpperCase() }
                    let [store, field] = k.split(':', 2)
                    if (!store || !field) { continue }
                    if (body[store] === undefined) { body[store] = {} }
                    body[store][field] = v
                }
                console.log(body, Object.keys(body))
                for (const store of Object.keys(body)) {
                    console.log(store)
                    if (!store) { continue }
                    const uid = body[store].uid
                    delete body[store].uid
                    krequest(new URL(`../api/${store}/${uid}`, window.location), {method: 'PUT', body: body[store]})
                    .then(response => {
                        Operation.log('Modification commande effectuée', {uid, type: 'commande'})
                    })
                    .catch(error => {
                        Operation.log('Erreur de modification', {uid, type: 'commande'})
                    })
                }
            })
            this.domNode.addEventListener('reset', event => {
                krequest(new URL(`../api/commande/${uid}`, window.location), {method: 'DELETE'})
                .then(response => {
                    Operation.log('Suppression commande', {uid: reference, type: 'commande'})

                    console.log(response)
                })
                .catch(error => {
                    Operation.log('Erreur suppression commande', {uid: reference, error, type: 'commande'})

                    console.log(error)
                })
            })
            resolve(['CMDE - Nouvelle commande', this.domNode])
        })
    })
}


function UpdateCommande() {
    return new NewCommande(true)
}