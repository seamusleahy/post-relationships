(function( $ ) {
$(document).ready(function() {


  
  //
  // Create the logic for each widget because there can be
  // more than one on a page.
  //
  $('div[data-widget="pr-advance-widget"]').each(function() {
    var widget = $(this);
    var name = widget.data('name');
    var listSource;
    var input = widget.find('input[name="'+name+'"]');


    // Process the items being resorted
    var resortItems = function() {
      var val = selectedList.children().map(function() {
        return $(this).data('id');
      }).get().join();
      input.val( val );
    };


    // Get the IDs
    var getIds = function( list ) {
      return list.find('.post').map(function() {
        return $(this).data('id');
      }).get();
    };

    // Switching list
    var fromList = '';
    var isSwitchedList = false;

    /**
     * Update the value
     */
    var updateValue = function() {
      selected = getIds( selectedList )
      input.val( selected.join(',') );
    };

    // Setup the list of selected items
    var selectedList = widget.find('.selected .post-list')
      .sortable({

        // Classes for the insert placeholder
        placeholder: 'sortable-placeholder post',

        // Record that the item was dragged from the pool list
        receive: function( event, ui ) {
          isSwitchedList = true;
        },

        // Track where the dragging started
        start: function() {
          fromList = 'selected';
          isSwitchedList = false;
        },

        // Update the value
        stop: function( event, ui ) {
          // For the case when an item is moved from selected list to the pool list
          if( isSwitchedList ) {
            dropPoolList( event, ui );
          }

          updateValue();
        }
      });

    // Setup the list of all items to pull from

    /**
     * Handles for moving item to correct place when an item is dropped on the pool
     */
    var dropPoolList = function( event, ui ) {
      var id = ui.item.data('id');
      var index = $.inArray( id, poolOrder);

      
      if( index > 0 ) {
        // If not the first item, find the item to insert after

        index = index - 1;
        var before = poolList.find('[data-id="'+poolOrder[index]+'"]');
        while( index >= 0 && before.length == 0 ) {
          --index;
          before = poolList.find('[data-id="'+poolOrder[index]+'"]');
        }

        if( before.length ) {
          // We found one before it to add after
          before.after( ui.item );
        } else {
          // Nothing before it, insert at the start
          poolList.prepend( ui.item );
        }

      } else if( index == 0 ) {
        // First item, insert at the start

        poolList.prepend( ui.item );
      } else {
        // The item is not in the pool
        ui.item.remove();
      }
    };

    var poolList = widget.find('.pool .post-list')
      .sortable({
        // Connect draggable with the selected list
        connectWith: 'div[data-name="'+name+'"] .selected .post-list',
        
        // The element you drag is a clone
        helper: 'clone',

        // collapse the insert placeholder to remove the UI reference of insert
        forcePlaceholderSize: false,

        // add the following classes to the placeholder element
        placeholder: 'sortable-placeholder post',

        // Move the items to the correct place within the pool list
        stop: function( event, ui ) {
          if( fromList == 'selected' || !isSwitchedList ) {
            dropPoolList( event, ui );
          }

          updateValue();
        },

        // Record that the item was dragged from the selected list
        receive: function( event, ui ) {
          isSwitchedList = true;
        },

        // Track where the dragging started
        start: function() {
          fromList = 'pool';
          isSwitchedList = false;
        }
      });

    // Connect the select list to pool list after pool list exists
    selectedList.sortable( 'option', 'connectWith', 'div[data-name="'+name+'"] .pool .post-list' );

    // Keep track of the item order in the pool
    var poolOrder = getIds( poolList );

    // Keep track of the selected items
    var selected = getIds( selectedList );

    // Remove items from pool that are selected
    poolList.find('.post').filter( function() {
      return $.inArray( $(this).data('id'), selected ) != -1;
    }).remove();



    // Query for the pool
    var poolQuery = {
      paged: 1,
      s: ''
    };
    var fetchingPoolQuery = {}; // The query currently being fetched
    var totalPoolPosts = null;

    /**
     * Check if query objects are equal
     */
    var areQueryObjectsEqual = function( q1, q2 ) {
      for( var q in q1 ) {
        if( q1[q] != q2[q] ) {
          return false;
        }
      }

      for( var q in q2 ) {
        if( q1[q] != q2[q] ) {
          return false;
        }
      }

      return true;
    };


    // Infinite scroll for the pool

    /**
     * Fetch the next page of pool posts
     */
    var fetchNextPage = function( callback ) {
      // Abort if we are already querying
      if( fetchingPoolQuery.paged == poolQuery.paged + 1 ) {
        return;
      }

      if( totalPoolPosts!==null && totalPoolPosts <= poolOrder.length ) {
        return;
      }

      fetchingPoolQuery = $.extend( { paged: 1 }, poolQuery );
      fetchingPoolQuery.paged = fetchingPoolQuery.paged + 1;

      var data = {
        action: 'pr_advance_ui_widget_pool_posts',
        post_id: widget.data('postId'),
        relationship: widget.data('relationship'),
        direction: widget.data('direction'),
        query: fetchingPoolQuery

      }

      $.getJSON( window.ajaxurl, data )
        .done( function( data, textStatus, jqXHR ) {

          if( areQueryObjectsEqual( data.query, fetchingPoolQuery ) ) {
            // Update query state
            poolQuery.paged = parseInt( data.query.paged );
            totalPoolPosts = data.total;
            fetchingPoolQuery = {};

            var items = $('<div>' + data.items + '</div>');
            // Update the order in the pool
            poolOrder = poolOrder.concat( getIds( items ) );
            // Remove items already in the selected
            items.find('.post').filter( function() {
              return $.inArray( $(this).data('id'), selected ) != -1;
            }).remove();

            poolList.append( items.children() );
            poolList.sortable( 'refresh' );

            if( callback ) {
              callback();
            }
          }
        });
    };

    //
    // Call the next page of pool posts when scrolling down or
    // when there are too few posts to fill the pool container
    //
    var onPoolScroll = function( event ) {
      var lastItem = poolList.children().eq(-1);
      var viewportHeight = poolList.height();
      var contentHeight = poolList.scrollTop() + (lastItem.offset().top - poolList.offset().top) + lastItem.outerHeight();
      if ( viewportHeight >  contentHeight ||
          poolList.scrollTop() + viewportHeight - 30 <= contentHeight ) {
        fetchNextPage( onPoolScroll );
      }
    };
    poolList.on( 'scroll', onPoolScroll);
    onPoolScroll();



    // Add buttons on the items
    poolList.on( 'click', '.actions [data-action="add"]', function( event ) {
      var item = $(event.target).closest('.post');

      selectedList.append( item ).sortable( 'refresh' );
      updateValue();
    });


    // Remove buttons on the items
    selectedList.on( 'click', '.actions [data-action="remove"]', function( event ) {
      var item = $(event.target).closest('.post');

      dropPoolList( event, { item: item });
      updateValue();
    });


    // Filters
    var filterPool = function( filters, callback ) {
      var newPoolQuery = $.extend( { }, poolQuery, filters );
      newPoolQuery.paged = 1;

      // Abort if we are already doing the query
      if( areQueryObjectsEqual( fetchingPoolQuery, newPoolQuery ) ) {
        return;
      }

      fetchingPoolQuery = newPoolQuery;

      var data = {
        action: 'pr_advance_ui_widget_pool_posts',
        post_id: widget.data('postId'),
        relationship: widget.data('relationship'),
        direction: widget.data('direction'),
        query: fetchingPoolQuery
      };

      $.getJSON( window.ajaxurl, data )
        .done( function( data, textStatus, jqXHR ) {

          if( areQueryObjectsEqual( data.query, fetchingPoolQuery ) ) {
            // Update query state
            poolQuery = fetchingPoolQuery;
            totalPoolPosts = data.total;
            fetchingPoolQuery = {};

            var items = $('<div>' + data.items + '</div>');
            // Update the order in the pool
            poolOrder = poolOrder.concat( getIds( items ) );
            // Remove items already in the selected
            items.find('.post').filter( function() {
              return $.inArray( $(this).data('id'), selected ) != -1;
            }).remove();

            poolList.empty();
            poolList.append( items.children() );
            poolList.sortable( 'refresh' );

            if( callback ) {
              callback();
            }
          }
        });
    };

    // Search
    var searchCallback;
    var searchFilter = widget.find( '.pool .filters [data-filter="search"]').on( 'input change', function( event ) {
      if( !searchCallback ) {
        searchCallback = setTimeout( updateSearchFilter, 300 );
      }
    });

    var updateSearchFilter = function() {
      searchCallback = false;
      var value = searchFilter.val();
      filterPool( { s: value }, onPoolScroll );
    };

  });
});
})( jQuery );