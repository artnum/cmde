function display (hash) {
    const content = document.getElementById('content')
    const titleNode = document.getElementById('title')

    if (hash === '') {
        hash = 'Index'
    }
    if (window[hash]) {
        const pageObject = new window[hash]()
        console.log(pageObject)
        pageObject.render()
        .then(([title, domNode]) => {
            console.log(title, domNode)
            window.requestAnimationFrame(() => {
                titleNode.innerHTML = title
                if (content.firstChild) { content.replaceChild(domNode, content.firstElementChild) }
                else { content.appendChild(domNode) }
            })
        })
    } else {
        const err = document.createElement('DIV')
        err.innerHTML = 'Erreur, inexsitant'
        window.requestAnimationFrame(() => { 
            title.innerHTML = "Erreur"
            if (content.firstElementChild) { content.replaceChild(err, content.firstElementChild) }
            else { content.appendChild(err) }
        })
    }
}
window.addEventListener('hashchange',(event) => {
    const page = String(new URL(event.newURL).hash).substring(1)
    display(page)
})
window.addEventListener('kcore-loaded', event => {
    const page = String(new URL(window.location).hash).substring(1)
    display(page)
})