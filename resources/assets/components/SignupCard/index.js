import React from 'react';
import { map } from 'lodash';
import PropTypes from 'prop-types';
import './signup-card.scss';
import PostTile from '../PostTile';
import { RestApiClient } from '@dosomething/gateway';
import { extractPostsFromSignups } from '../../helpers';

class SignupCard extends React.Component {
  render() {
    const signup = this.props.signup;
    const campaign = this.props.campaign;
    const gallerySize = 4;

    const extraPostCount = signup.posts.data.length - gallerySize;

    const posts = signup.posts.data
      .slice(0, gallerySize)
      .map(post => <PostTile key={post.id} post={post} />);

    const signupUrl = `/signups/${signup.signup_id}`;

    return (
      <article className="container__row signup-card">
        <a href={signupUrl}>
          <div className="container__block -half">
            <div className="container__row">
              <h2 className="heading">
                {campaign ? campaign.internal_title : signup.campaign_id}
              </h2>
              <h4 className="heading">Campaign ID: {signup.campaign_id}</h4>
              {campaign ? (
                <h4 className="heading">
                  Campaign Start Date: {campaign.start_date}
                </h4>
              ) : null}
            </div>
            <div className="container__row">
              <h4 className="heading">Why Statement</h4>
              <p>{signup.why_participated}</p>
            </div>
          </div>
          <div className="container__block -half">
            {signup.quantity ? (
              <div className="container__row figure -left -center">
                <div className="figure__media">
                  <div className="quantity">{signup.quantity}</div>
                </div>
                <div className="figure__body">
                  <h4 className="reportback-noun-verb">things done</h4>
                </div>
              </div>
            ) : null}
            {posts.length ? (
              <div className="container__row">
                <h4>Items</h4>
                <ul className="gallery">
                  {posts}
                  {extraPostCount > 0 ? (
                    <li className="figure__media">
                      <div className="quantity">+{extraPostCount}</div>
                    </li>
                  ) : null}
                </ul>
              </div>
            ) : null}
          </div>
        </a>
      </article>
    );
  }
}

SignupCard.propTypes = {
  signup: PropTypes.shape({
    posts: PropTypes.object,
    why_participated: PropTypes.string,
    signup_id: PropTypes.number,
    campaign_id: PropTypes.string,
    quantity: PropTypes.number,
  }).isRequired,
  campaign: PropTypes.shape({
    internal_title: PropTypes.string,
    start_date: PropTypes.string,
  }),
};

SignupCard.defaultProps = {
  campaign: null,
};

export default SignupCard;
