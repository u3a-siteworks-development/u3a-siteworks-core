// Make sure a title is entered
// Ref https://bdwm.be/gutenberg-how-to-prevent-post-from-being-saved/

let locked = false;

wp.data.subscribe(() => {
    // get the current title
    const postTitle = wp.data.select('core/editor').getEditedPostAttribute('title');

    // Lock the post if the title is empty.
    if (!postTitle) {
        if (!locked) {
            locked = true;
            wp.data.dispatch('core/editor').lockPostSaving('title-lock');
            wp.data.dispatch('core/notices').createNotice(
                'error',
                'Please enter the name below here, as requested',
                { id: 'title-lock', isDismissible: false }
            );
        }
    } else if (locked) {
        locked = false;
        wp.data.dispatch('core/editor').unlockPostSaving('title-lock');
        wp.data.dispatch('core/notices').removeNotice('title-lock');
    }
});