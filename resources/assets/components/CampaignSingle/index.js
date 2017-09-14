import React from 'react';
import { map } from 'lodash';
import { RestApiClient} from '@dosomething/gateway';
import { extractSignupsFromPosts } from '../../helpers';

import Post from '../Post';
import FilterBar from '../FilterBar';
import DropdownFilter from '../DropdownFilter';
import MultiValueFilter from '../MultiValueFilter';
import HistoryModal from '../HistoryModal';
import PagingButtons from '../PagingButtons';
import StatusCounter from '../StatusCounter';
import ModalContainer from '../ModalContainer';

class CampaignSingle extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      loadingNewPosts: false
    };

    this.api = new RestApiClient;
    this.filterPosts = this.filterPosts.bind(this);
    this.getPostsByFilter = this.getPostsByFilter.bind(this);
    this.getPostsByPaginatedLink = this.getPostsByPaginatedLink.bind(this);
  }

  // Filter posts based on status or tag(s).
  filterPosts(state) {
    // Grab all of the active tags to send to API request.
    let activeTags = [];

    if (state.tags) {
      Object.keys(state.tags).forEach(function(key) {
        if (state.tags[key] === true) {
         activeTags.push(key);
        }
      });
    }

    let filters = {
      'campaignId': this.props.campaign.id,
      'status': state.status,
      'tags': activeTags,
    };

    this.getPostsByFilter(filters);
  }

  // Make API call to paginated link to get next/previous batch of posts.
  getPostsByPaginatedLink(url, event) {
    event.preventDefault();

    this.setState({ loadingNewPosts: true });

    // Strip the url to get query parameters.
    let splitEndpoint = url.split('/');
    let path = splitEndpoint.slice(-1)[0];
    let queryString = (path.split('?'))[1];

    this.api.get('posts', queryString)
    .then(json => {
      this.setState({loadingNewPosts: false });
      this.props.setNewPosts(json);
    });
  }

  // Make API call to GET /posts to get posts by filtered status and/or tag(s).
  getPostsByFilter(filters) {
    this.setState({ loadingNewPosts: true });

    let apiFilter = {
      campaign_id: filters.campaignId,
      status: filters.status,
    };

    if (filters.tags.length > 0) {
      apiFilter['tag'] = filters.tags.toString();
    }

    this.api.get('api/v2/posts', {
      filter: apiFilter,
      include: 'signup,siblings',
    })
    .then(json => {
      this.setState({loadingNewPosts: false });
      this.props.setNewPosts(json);
    });
  }

  render() {
    const posts = this.props.posts;
    const campaign = this.props.campaign;
    const signups = this.props.signups;

    return (
      <div className="container">
        <StatusCounter postTotals={this.props.post_totals} campaign={campaign} />

        <h2 className="heading -emphasized">Post Filters</h2>
        <FilterBar onSubmit={this.filterPosts}>
          <DropdownFilter options={{accepted: 'Accepted', pending: 'Pending', rejected: 'Rejected'}} onChange={this.updateFilters} />
          <MultiValueFilter options={{'good-photo': 'Good Photo', 'good-quote': 'Good Quote', 'hide-in-gallery': 'Hide In Gallery 👻', 'good-for-sponsor': 'Good For Sponsor', 'good-for-storytelling': 'Good For Storytelling'}} onChange={this.updateFilters} />
        </FilterBar>

        <h2 className="heading -emphasized">Posts</h2>
        {this.props.loading || this.state.loadingNewPosts ?
          <div className="spinner"></div>
        :
          map(posts, (post, key) =>
            <Post key={key}
              post={post}
              user={signups[post.signup_id].user.data}
              signup={signups[post.signup_id]}
              campaign={campaign}
              onUpdate={this.props.updatePost}
              onTag={this.props.updateTag}
              deletePost={this.props.deletePost}
              showHistory={this.props.showHistory}
              showSiblings={true}
              showQuantity={true}
              allowHistory={true} />
          )
        }

        <ModalContainer>
          {this.props.displayHistoryModal ?
            <HistoryModal
              id={this.props.historyModalId}
              onUpdate={this.props.updateQuantity}
              onClose={e => this.hideHistory(e)}
              campaign={campaign}
              signup={signups[posts[this.props.historyModalId]['signup_id']]}
            />
          : null}
        </ModalContainer>

        <PagingButtons onPaginate={this.getPostsByPaginatedLink} prev={this.props.prevPage} next={this.props.nextPage}></PagingButtons>
      </div>
    )
  }
}

export default CampaignSingle;
