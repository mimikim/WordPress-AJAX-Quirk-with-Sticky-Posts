# Funky Quirk with WordPress and Loading Sticky Posts via AJAX

## The Problem
WordPress will not load Sticky Posts at the top (per default functionality) when loading posts via AJAX

## Description

A while back, I was writing out functionality for a client who wanted to load blog posts via AJAX. This is a very simple procedure, just hook up *wp_ajax* to your desired function and make sure your javascript defines this action.

The problem was that no matter what I did, sticky posts would not show up at the top! By default, they ought to. At first, I realized I was using get_posts, which does not support sticky posts. So I switched my code up to use WP_Query but that did not do anything. The posts were still outputting in their chronological order!

What's going on! >__<

There was nothing wrong with my query, I would paste the code into a random page to see if it would output. I noticed that when I ran this query in a Page or Post, it would run successfully with all Stickies at the top. How strange...

Upon further investigaton, I discovered that this was happening because I was running my AJAX in an independent PHP file. This PHP file was not associated with any post_id, since it was neither a Page nor a Post but rather just a file that contained a function.

When running WP_Query, it checks to see if there are sticky posts by running a check to see if there is an associated post_id (with a *is_page*/*is_post* boolean check). Because this check fails, WP_Query is unable to fulfill its default sticky_post function.

You can check out with code in */wp-includes/query.php*, starting at line 3751.

## Solution

Well, there are many ways around it but feel free to check out my solution in ajax_function.php to see how I was able to solve this dilemma.

I am using *get_posts* because it would not matter either way (since default sticky post function would not work). You can use WP_Query and simply target posts.

Essentially, I am running a check to see if there are any sticky posts to begin with. If there are not, we can simply assemble posts and output as normal.

If there are sticky posts, we first get a list of all sticky posts as well as a list of all queried posts (based off category or post-format selection, if any). We merge the arrays such that the sticky post array is in the front, and then we remove any duplicate posts. For the sake of ease, we are simply targetting post IDs.

*wp_list_pluck* is an absolute god-send function that WordPress provides.

We would then re-index the merged, non-duplicate array and send out slices which correspond with your desired post-per-page.

And that's how it's done :)
