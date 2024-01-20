(function(wp){
    const { subscribe, select } = wp.data;

    let isSaving = false;
    let didSave = false;

    // Function to update cost and length
    function updateCostAndLength() {
        const editor = select('core/editor');
        if (!editor || !editor.getEditedPostContent) {
            // Retry after a short delay if the editor is not ready
            setTimeout(updateCostAndLength, 500);
            return;
        }

        const newContent = editor.getEditedPostContent();
        const newTitle = editor.getEditedPostAttribute('title');

        const textContent = newContent.replace(/<\/?[^>]+(>|$)/g, "").replace(/<!--[\s\S]*?-->/g, "");
        const totalLength = textContent.length + newTitle.length;
        const cost = (totalLength / 1000) * 0.015;

        document.getElementById('tts-cost-amount').innerHTML = '$' + cost.toFixed(4);
        document.getElementById('tts-cost-characters').innerHTML = totalLength;
    }

    // Try to run on page load after 4 seconds
    setTimeout(updateCostAndLength, 4000);

    subscribe(() => {
        const editor = select('core/editor');
        if (!editor) {
            return;
        }

        const isSavingPost = editor.isSavingPost();
        const didSaveSucceed = editor.didPostSaveRequestSucceed();
        const didSaveFail = editor.didPostSaveRequestFail();

        if (isSaving && (didSaveSucceed || didSaveFail)) {
            didSave = true;
        }

        isSaving = isSavingPost;

        if (didSave) {
            didSave = false;
            updateCostAndLength();
        }
    });
})(window.wp);