/* eslint-disable */

import React from 'react';
import { keyBy, map } from 'lodash';
import RogueClient from '../../utilities/RogueClient';
import {
  extractPostsFromSignups,
  extractSignupsFromPosts,
} from '../../helpers';

const reviewComponent = (Component, data) => {
  return class extends React.Component {
    constructor(props) {
      super(props);

      this.state = {
        loading: true,
      };

      this.api = new RogueClient(window.location.origin, {
        headers: { Authorization: `Bearer ${window.AUTH.token}` },
      });
      this.updatePost = this.updatePost.bind(this);
      this.updateTag = this.updateTag.bind(this);
      this.updateQuantity = this.updateQuantity.bind(this);
      this.showHistory = this.showHistory.bind(this);
      this.hideHistory = this.hideHistory.bind(this);
      this.deletePost = this.deletePost.bind(this);
      this.setNewPosts = this.setNewPosts.bind(this);
      this.rotate = this.rotate.bind(this);
    }

    // Loads initial posts into state.
    componentDidMount() {
      // Require initial_posts and campaign parameters.
      if (data.initial_posts && data.campaign) {
        this.getPostsByStatus(data.initial_posts, data.campaign.id);
      } else {
        // @TODO - handle error better.
        console.log(
          'Error: need to know the initial posts to load and the campaign.',
        );
      }
    }

    // Make API call to GET api/v3/posts to get posts by filtered status.
    getPostsByStatus(status, campaignId) {
      this.api
        .getPosts({
          filter: {
            status: status,
            campaign_id: campaignId,
            type: 'photo,text',
          },
          include: ['signup', 'siblings'],
        })
        .then(json =>
          this.setState({
            campaign: data.campaign,
            posts: keyBy(json.data, 'id'),
            postIds: map(json.data, 'id'),
            signups: extractSignupsFromPosts(keyBy(json.data, 'id')),
            filter: status,
            displayHistoryModal: null,
            historyModalId: null,
            loading: false,
            nextPage: json.meta.cursor.next,
            prevPage: json.meta.cursor.prev,
          }),
        );
    }

    setNewPosts(apiResponse) {
      const posts = keyBy(apiResponse.data, 'id');
      this.setState({
        campaign: data.campaign,
        posts: posts,
        postIds: map(apiResponse.data, 'id'),
        signups: extractSignupsFromPosts(posts),
        displayHistoryModal: null,
        historyModalId: null,
        loading: false,
        nextPage: apiResponse.meta.cursor.next,
        prevPage: apiResponse.meta.cursor.prev,
      });
    }

    // Open the history modal of the given post
    showHistory(postId, event, signupId) {
      event.preventDefault();

      this.api
        .getEvents({
          filter: {
            signup_id: signupId,
          },
        })
        .then(result => {
          this.setState(previousState => {
            const newState = { ...previousState };

            newState.displayHistoryModal = true;
            newState.historyModalId = postId;
            newState.signupEvents = Object.values(result.data);
            return newState;
          });
        });
    }

    // Close the open history modal
    hideHistory(event) {
      if (event) {
        event.preventDefault();
      }

      this.setState({
        displayHistoryModal: false,
        historyModalId: null,
      });
    }

    // Updates a post status.
    updatePost(post, fields) {
      fields.post_id = post.id;

      let request = this.api.postReview(fields);

      return request.then(result => {
        this.setState(previousState => {
          const newState = { ...previousState };

          newState.posts[post.id].status = fields.status;

          return newState;
        });
      });
    }

    // Tag a post.
    updateTag(postId, tag) {
      const field = {
        tag_name: tag,
      };

      let response = this.api.post(`api/v3/posts/${postId}/tags`, field);

      return response.then(result => {
        this.setState(previousState => {
          const newState = { ...previousState };
          const user = newState.posts[postId].user;
          const signup = newState.posts[postId].signup.data;

          // Merge existing post with the newly updated values from API.
          newState.posts[postId] = {
            ...newState.posts[postId],
            ...result['data'],
          };

          return newState;
        });
      });
    }

    // Update a post's quantity.
    updateQuantity(post, newQuantity) {
      // Field to send to /api/v3/posts/:post_id
      const field = {
        quantity: parseInt(newQuantity),
      };

      // Make API request to Rogue to update the quantity on the backend
      let request = this.api.patch(`api/v3/posts/${post['id']}`, field);

      request.then(result => {
        // Update the state
        this.setState(previousState => {
          const newState = { ...previousState };
          newState.posts[post['id']].quantity = result.data['quantity'];

          return newState;
        });
      });

      // Close the modal
      this.hideHistory();
    }

    // Delete a post.
    deletePost(postId, event) {
      event.preventDefault();
      const confirmed = confirm(
        '🚨🔥🚨Are you sure you want to delete this?🚨🔥🚨',
      );

      if (confirmed) {
        // Make API request to Rogue to update the quantity on the backend
        let response = this.api.delete(`api/v3/posts/${postId}`);

        response.then(result => {
          // Update the state
          this.setState(previousState => {
            var newState = { ...previousState };

            // Remove the deleted post from the state
            delete newState.posts[postId];

            // Remove the postId from the state.
            const postIdIndex = newState.postIds.indexOf(postId);

            if (postIdIndex > -1) {
              newState.postIds.splice(postIdIndex, 1);
            }

            // Return the new state
            return newState;
          });
        });
      }
    }

    // Rotate a Post Image.
    rotate(postId) {
      let response = this.api.post(`images/${postId}?rotate=90`);

      return response.then(json => {
        this.setState(prevState => {
          const newState = { ...prevState };
          // Add a cache-busting string to the end of the image url
          // so that it changes and triggers a re-render.
          newState.posts[postId].media.original_image_url =
            json.original_image_url + '?time=' + Date.now();

          return newState;
        });
      });
    }

    render() {
      const methods = {
        updatePost: this.updatePost,
        updateTag: this.updateTag,
        updateQuantity: this.updateQuantity,
        showHistory: this.showHistory,
        hideHistory: this.hideHistory,
        deletePost: this.deletePost,
        setNewPosts: this.setNewPosts,
        rotate: this.rotate,
      };

      // Pass in the state from this HoC to trigger rendering down the DOM
      // Also pass in methods bound to this instance.
      // Also pass in original data pass to the HoC in case other components still need it down the line.
      return <Component {...this.state} {...methods} {...data} />;
    }
  };
};

export default reviewComponent;
