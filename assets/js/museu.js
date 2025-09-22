const museuMuseusBrPerformWhenDocumentIsLoaded = callback => {
    if (/comp|inter|loaded/.test(document.readyState))
        callback();
    else
        document.addEventListener('DOMContentLoaded', callback, false);
}

function hideUnecessaryGalleryNavIcon() {
    const gallerySection = document.querySelector('.tainacan-item-section--special-museusbr-gallery');
    const documentNavIcon = document.getElementById('tainacan-item-documents-label-nav');
    
    if ( !documentNavIcon )
        return;

    if ( !gallerySection || gallerySection.childElementCount == 0 )
        documentNavIcon.style.display = 'none';
}

function defineAmountOfTabs() {
    const sectionLabels = document.querySelector('.metadata-section-layout--tabs');

    if ( !sectionLabels )
        return;

    const totalOfTabbedSections = sectionLabels.childElementCount;

    if ( totalOfTabbedSections >= 3 )
        sectionLabels.style.setProperty('--section-tabs-count', Math.round(totalOfTabbedSections/3));
}

museuMuseusBrPerformWhenDocumentIsLoaded(() => {
    hideUnecessaryGalleryNavIcon();
    defineAmountOfTabs();
});
