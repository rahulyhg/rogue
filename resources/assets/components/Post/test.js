import React from 'react';
import renderer from 'react-test-renderer';

import Post from './index';

test('it renders correctly', () => {
  const post = {
    id: 123,
    action_details: {
      data: {
        name: 'default',
      },
    },
    media: {
      text: 'Here is my awesome caption!',
    },
    status: 'pending',
    tags: [],
    type: 'photo',
  };

  const signup = {
    signup_id: 7,
  };

  const campaign = {
    data: {
      internal_title: 'Awesome Campaign',
    },
  };

  const deletePost = function() {
    return true;
  };

  const onUpdate = function() {
    return true;
  };

  const onTag = function() {
    return true;
  };

  const tree = renderer
    .create(
      <Post
        post={post}
        campaign={campaign}
        signup={signup}
        deletePost={deletePost}
        onUpdate={onUpdate}
        onTag={onTag}
      />,
    )
    .toJSON();
  expect(tree).toMatchSnapshot();
});
