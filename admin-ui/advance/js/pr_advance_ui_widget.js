(function( $ ) {
  var root = this;
  var pra = root.prAdvanceUi = {}; // Make them aviable in case others want to use them
  var bb = Backbone; // shortcut


  //
  // Utility
  //
  _.extendReturn = function() {
    var r = {};
    var arg = Array.prototype.slice.call(arguments, 0);
    arg.unshift( r );
    _.extend.apply( _, arg );
    return r;
  }

  /**
   *  Model of IDs in order
   */
  pra.IdsOrderModel = bb.Model.extend({
    defaults: { ids: [] },

    append: function( id ) {
      var ids = this.get( 'ids' );
      ids.append(id);
      this.set( 'ids', ids );
    },

    update: function( ids ) {
      this.set( 'ids', ids );
    },

    getIds: function( ) {
      return this.get( 'ids' );
    },

    indexOf: function( id ) {
      return _.indexOf( this.attributes.ids, id );
    },

    at: function( index ) {
      return this.attributes.ids[index];
    },

    updateFromElements: function( elements ) {
      this.update( this._getIdsFromElements( elements ) );
    },

    _getIdsFromElements: function( elements ) {
      return elements.map(function() {
        return $(this).data('id');
      }).get();
    }
  });


  pra.PoolIdsOrderModel = pra.IdsOrderModel.extend({
    initialize: function() {
      this.isFetching = false;
      // For tracking what has changed since the last update because multiple updates could occure before AJAX respondes
      this.fetchingQuery = {};
      this.fetchingPaged = 1;

      this.paged = 1;
      this.total = null;

      _.bindAll( this, 'onQueryChange', 'fetchedNextPage', 'fetchedQueryChanged', 'onQueryChangeAjaxCall' );
    },

    setQueryModel: function( queryModel ) {
      this.queryModel = queryModel;
      this.queryModel.on( 'change', this.onQueryChange );
    },

    setSelectedModel: function( selectedModel ) {

    },

    setAjaxData: function( data ) {
      this.ajaxData = data;
    },

    onQueryChange: function( queryModel, state ) {
      this.isFetching = true;

      var query = this.fetchingQuery = _.extend( {}, this.queryModel.attributes, { paged: 1 } );
      this.ajaxData = _.extend( this.ajaxData, { query: query });  

      // Add a delay up to 200ms before making the AJAX call to allow multiple quick changes to happen at once
      if ( !this.onQueryChangeAjaxCallTimer ) {
        this.onQueryChangeAjaxCallTimer = setTimeout( this.onQueryChangeAjaxCall, 150 );
      }
    },

    onQueryChangeAjaxCall: function() {
      this.onQueryChangeAjaxCallTimer = null;
      $.getJSON( this.ajaxData.url, this.ajaxData )
        .done( this.fetchedQueryChanged );
    },

    fetchNextPage: function() {
      // Already fetching something, don't do a next page fetch in that case
      if ( this.isFetching ) {
        return;
      }
      
      // Don't bother, we are at the end
      if( this.total!==null && this.total <= this.attributes.ids.length ) {
        return;
      }

      this.isFetching = true;
      
      var query = this.fetchingQuery = _.extend( {}, this.queryModel.attributes, {paged: this.paged + 1} );
      var data = _.extend( this.ajaxData, { query: query });

      $.getJSON( this.ajaxData.url, data ).done( this.fetchedNextPage );
    },

    fetchedNextPage: function( data, textStatus, jqXHR ) {
      var query = data.query;
      var paged = parseInt( query.paged );

      if( this.areQueryObjectsEqual( this.fetchingQuery, query ) ) {
        this.isFetching = false;

        // Update query state
        this.paged = paged;
        this.total = data.total;
        this.fetchingQuery = {};


        // Add the next IDs
        var items = $('<div>' + data.items + '</div>').children();
        var ids = this.getIds();
        var newIds = this._getIdsFromElements( items );
        this.set( 'ids', ids.concat( newIds ) );

        this.trigger( 'nextPage', items );
      }
    },


    fetchedQueryChanged: function( data, textStatus, jqXHR ) {
      var query = data.query;
      var paged = parseInt( query.paged );

      if( this.areQueryObjectsEqual( this.fetchingQuery, query ) ) {
        this.isFetching = false;

        // Update query state
        this.paged = paged;
        this.total = data.total;
        this.fetchingQuery = {};


        // Add the next IDs
        var items = $('<div>' + data.items + '</div>');
        var ids = this.getIds();
        var newIds = this._getIdsFromElements( items );
        this.set( 'ids', newIds );

        this.trigger( 'queryChanged', items.children() );
      }
    },

    areQueryObjectsEqual: function( q1, q2 ) {
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
    }
  });


  /**
   * The query for the pool
   */
  pra.PoolQueryModel = bb.Model.extend({
    defaults: {
      paged: 1,
      s: ''
    },

    isEqualTo: function( query ) {
      for( var q in query ) {
        if( this.attributes[q] != query[q] ) {
          return false;
        }
      }

      for( var q in this.attributes ) {
        if( this.attributes[q] != query[q] ) {
          return false;
        }
      }

      return true;
    },


  });



  /**
   * Widget view
   */
  pra.WidgetView = bb.View.extend({
    initialize: function() {
      this.queryModel = new pra.PoolQueryModel;
      this.poolModel = new pra.PoolIdsOrderModel();
      this.selectedModel = new pra.IdsOrderModel();
      
      this.poolModel.setQueryModel( this.queryModel );
      this.poolModel.setSelectedModel( this.selectedModel );

      _.bindAll( this, 'updateInputValue' );
    },

    setup: function() {
      this.name = this.$el.data('name');
      this.input = this.$el.find('input[name="'+this.name+'"]');

      
      this.poolModel.setAjaxData({
        url: window.ajaxurl,
        action: 'pr_advance_ui_widget_pool_posts',
        post_id: this.$el.data('postId'),
        relationship: this.$el.data('relationship'),
        direction: this.$el.data('direction'),
      });

      this.poolView = new pra.PoolView({ el: this.$el.find('.pool .post-list'), model: this.poolModel, selectedModel: this.selectedModel, prependPostsWhenAddingToSelected: this.$el.data( 'addToStartOfSelected' ) });
      this.selectedView = new pra.SelectedView({ el: this.$el.find('.selected .post-list'), model: this.selectedModel });
      this.filterView = new pra.FilterView({ el: this.$el.find('.pool .filters'), model: this.queryModel });

      this.poolView.setup();
      this.selectedView.setup();
      this.filterView.setup();

      this.poolView.setSelectedView( this.selectedView );
      this.selectedView.setPoolView( this.poolView );

      this.selectedModel.on( 'change', this.updateInputValue );
    },

    updateInputValue: function() {
      this.input.val( this.selectedModel.getIds().join(',') );
    }
  });

  /**
   * Post List
   */
   pra.PostList = bb.View.extend({
    events: {
      'click [data-action="toggle-expanded"]': 'toggleExpanded'
    },

    toggleExpanded: function( event ) {
      var toggle = $(event.target);
      var item = toggle.closest( '.post' );
      var expanded = item.find('.expanded-view');

      var showing = expanded.hasClass('show');

      expanded.toggleClass( 'show', !showing );
      toggle.text( toggle.data( !showing ? 'hideText' : 'showText' ) );

      return false;
    }
   });



  /**
   * Pool list
   */
  pra.PoolView = pra.PostList.extend({

    events: _.extendReturn( pra.PostList.prototype.events, {
      'click [data-action="add"]': 'addItem',
      'scroll': 'loadNextPageWhenReady'
    }),

    /**
     * Init
     */
    initialize: function( attrs ) {
      this.dragData = {};

      _.bindAll( this, 'sortStop', 'sortStart', 'sortReceive', 'removeSelectedItems', 'loadNextPageWhenReady', 'insertNextPage', 'updateResults' );

      this.selectedModel = attrs.selectedModel;
      this.prependPostsWhenAddingToSelected = !!attrs.prependPostsWhenAddingToSelected;

      this.selectedModel.on( 'change', this.removeSelectedItems );
      this.removeSelectedItems();


      
    },

    /**
     * Use to setup the view to be usable.
     *
     * This is the replacement for 'render' because we are not generating an HTML,
     * instead attaching to the existing HTML.
     */
    setup: function() {
      this.$el.sortable({       
        // The element you drag is a clone
        helper: 'clone',

        // collapse the insert placeholder to remove the UI reference of insert
        forcePlaceholderSize: false,

        // add the following classes to the placeholder element
        placeholder: 'sortable-placeholder post',

        // Move the items to the correct place within the pool list
        stop: this.sortStop,

        // Record that the item was dragged from the selected list
        receive: this.sortReceive,

        // Track where the dragging started
        start: this.sortStart
      });

      this.model.updateFromElements( this.$el.children('.post') );
      this.model.on( 'nextPage', this.insertNextPage );
      this.model.on( 'queryChanged', this.updateResults );

      this.loadNextPageWhenReady();

      this.on( 'updated', this.loadNextPageWhenReady );
    },


    /**
     * Remove the selected items
     */
    removeSelectedItems: function() {
      var ids = this.selectedModel.getIds();
      this.$el.children().filter(function() {
        var id = $(this).data('id');
        if ( id ) {
          return $.inArray( id, ids ) != -1;
        } else {
          return false;
        }
      }).remove();
      
      this.trigger( 'updated' );
    },

    /**
     * Set the Selected View instances this will work with
     */
    setSelectedView: function( selectedView ) {
      this.selectedView = selectedView;

      // Use a shared drag data object to know where items are coming from
      selectedView.dragData = this.dragData;
      
      // Allowed items to be dragged to selected
      selectedView.$el.uniqueId();
      this.$el.sortable( 'option', 'connectWith', '#'+selectedView.$el.attr('id') );
    },


    sortStop: function( event, ui ) {
      if( this.dragData.fromList == 'selected' || !this.dragData.isSwitchedList ) {
        this.insertItem( ui.item );
      } else if( this.dragData.fromList == 'pool' && this.dragData.isSwitchedList ) {
        this.selectedView.insertItem( ui.item );
      }
      
    },

    sortStart: function() {
      this.dragData.fromList = 'pool';
      this.dragData.isSwitchedList = false;
    },

    sortReceive: function( event, ui ) {
      this.dragData.isSwitchedList = true;
    },

    insertItem: function( item ) {
      var id = item.data('id');
      var index = this.model.indexOf( id );
      
      if( index > 0 ) {
        // If not the first item, find the item to insert after

        index = index - 1;
        var before = this.$el.find('[data-id="'+this.model.at(index)+'"]');
        while( index >= 0 && before.length == 0 ) {
          --index;
          before = this.$el.find('[data-id="'+this.model.at(index)+'"]');
        }

        if( before.length ) {
          // We found one before it to add after
          before.after( item );
        } else {
          // Nothing before it, insert at the start
          this.$el.prepend( item );
        }

      } else if( index == 0 ) {
        // First item, insert at the start

        this.$el.prepend( item );
      } else {
        // The item is not in the pool
        item.remove();
      }

      this.trigger( 'updated' );
    },


    addItem: function( event ) {
      var item = $(event.target).closest('.post');

      item.remove();
      this.selectedView.insertItem( item, this.prependPostsWhenAddingToSelected );

      this.trigger( 'updated' );
    },

    loadNextPageWhenReady: function( event ) {

      // Load the next page if it is empty
      if ( this.$el.children().length == 0 ) {
        this.model.fetchNextPage();
        return;
      }

      var lastItem = this.$el.children().eq(-1);
      var viewportHeight = this.$el.height();
      var contentHeight = this.$el.scrollTop() + (lastItem.offset().top - this.$el.offset().top) + lastItem.outerHeight();


      if ( viewportHeight >  contentHeight ||
          this.$el.scrollTop() + viewportHeight >= contentHeight - 30) {
        this.model.fetchNextPage();
      }
    },

    insertNextPage: function( items ) {
      
      var selectedIds = this.selectedModel.getIds();
      items = items.filter( function() {
        return $.inArray( $(this).data('id'), selectedIds ) == -1;
      });

      this.$el.append( items );
      this.$el.sortable( 'refresh' );

      // Call to ensure the list is filled to the bottom
      this.loadNextPageWhenReady();
    },

    updateResults: function( items ) {
      this.$el.empty().append( items );
      this.$el.sortable( 'refresh' );

      // Call to ensure the list is filled to the bottom
      this.loadNextPageWhenReady();
    }
  });


  /**
   * Selected list
   */
  pra.SelectedView = pra.PostList.extend({

    events: _.extendReturn( pra.PostList.prototype.events, {
      'click [data-action="remove"]': 'removeItem'
    }),

    /**
     * Init
     */
    initialize: function() {
      this.dragData = {};

      _.bindAll( this, 'sortStop', 'sortStart', 'sortReceive' );
    },

    /**
     * Use to setup the view to be usable.
     *
     * This is the replacement for 'render' because we are not generating an HTML,
     * instead attaching to the existing HTML.
     */
    setup: function() {
      this.$el.sortable({

        // Classes for the insert placeholder
        placeholder: 'sortable-placeholder post',

        // Record that the item was dragged from the pool list
        receive: this.sortReceive,

        // Track where the dragging started
        start: this.sortStart,

        // Update the value
        stop: this.sortStop
      });

      this.model.updateFromElements( this.$el.children('.post') );
    },

    sortStop: function( event, ui ) {
      // For the case when an item is moved from selected list to the pool list
      if( this.dragData.isSwitchedList ) {
        this.poolView.insertItem( ui.item );
      }

      this.model.updateFromElements( this.$el.children('.post') );
    },

    sortStart: function() {
      this.dragData.fromList = 'selected';
      this.dragData.isSwitchedList = false;
    },

    sortReceive: function( event, ui ) {
      this.dragData.isSwitchedList = true;
    },


    /**
     * Set the Selected View instances this will work with
     */
    setPoolView: function( poolView ) {
      this.poolView = poolView;

      // Use a shared drag data object to know where items are coming from
      poolView.dragData = this.dragData;
      // Allowed items to be dragged to pool
      poolView.$el.uniqueId();
      this.$el.sortable( 'option', 'connectWith', '#'+poolView.$el.attr('id') );
    },

    insertItem: function( item, prepend ) {
      if( item.parent().get(0) != this.el ) {
        if ( prepend ) {
          this.$el.prepend( item );
        } else {
          this.$el.append( item );
        }
      }
      this.model.updateFromElements( this.$el.children('.post') );
    },

    removeItem: function( event ) {
      var item = $(event.target).closest('.post');

      item.remove();
      this.poolView.insertItem( item );
      this.model.updateFromElements( this.$el.children('.post') );
    }
  });


  /**
   * View for the pool filters
   */
  pra.FilterView = bb.View.extend({

    events: {
      'input  input[type="text"]':   'filterChange',
      'change input[type="text"]':   'filterChange',
      'input  input[type="search"]': 'filterChange',
      'change input[type="search"]': 'filterChange',
      'change select':               'filterChange'
    },

    initialize: function() {

    },


    setup: function() {
      
    },


    filterChange: function( event ) {
      var input = $(event.target);
      var name = input.data('filter');

      this.model.set( name, input.val() );
    }
  });


$(document).ready(function() {
  // Initialize
  $('div[data-widget="pr-advance-widget"]').each(function() {
    var widget = new pra.WidgetView({ el: this });
    widget.setup();
  });
});
})( jQuery );