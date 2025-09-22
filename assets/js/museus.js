const museusMuseusBrPerformWhenDocumentIsLoaded = callback => {
    if (/comp|inter|loaded/.test(document.readyState))
        callback();
    else
        document.addEventListener('DOMContentLoaded', callback, false);
}

function changeItemsListLabels() {
    const exposersButton = document.getElementById('tainacanExposersButton');
    
    if ( exposersButton && exposersButton.children[0] && exposersButton.children[0].lastChild ) 
        exposersButton.children[0].lastChild.innerText = 'Baixar'
}

museusMuseusBrPerformWhenDocumentIsLoaded(() => {
    document.addEventListener('tainacan-items-list-is-loading-items', changeItemsListLabels);
});
