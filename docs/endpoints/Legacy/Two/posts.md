## Posts

## Retrieve all Posts

```
GET /api/v2/posts
```

Posts are returned in reverse chronological order.

### Optional Query Parameters

- **limit**
  - Set the number of records to return in a single response.
  - e.g. `/posts?limit=35`
- **page** _(integer)_
  - For pagination, specify page of activity to return in the response.
  - e.g. `/posts?page=2`
- **campaign_id** _(integer)_
  - The nid to filter the response by.
  - e.g. `/posts?filter[campaign_id]=47`
- **northstar_id** _(integer)_
  - The northstar_id to filter the response by.
  - e.g. `/posts?filter[northstar_id]=47asdf23abc`
- **status** _(string)_
  - The string to filter the response by.
  - e.g. `/posts?filter[status]=accepted`
- **exclude** _(integer)_
  - The post id(s) to exclude in response.
  - e.g. `/posts?filter[exclude]=2,3,4`
- **as_user** _(string)_
  - The logged in user to display if they have reacted to the post or not.
  - e.g. `/posts?as_user=1234`
- **include** _(string)_
  - Include additional related records in the response: `signup`, `siblings`
  - e.g. `/posts?include=signup,siblings`
- **tag** _(string)_
  - The tag(s) to filter the response by.
  - Tag is passed in as tag_slug.
  - e.g. `/posts?filter[tag]=good-submission,good-for-sponsor`

Example Response:

```
{
    "data": [
        {
            "id": 2984,
            "signup_id": 4673,
            "northstar_id": "5594429fa59dbfc9578b48f4",
            "media": {
                "url": "https://s3.amazonaws.com/ds-rogue-qa/uploads/reportback-items/edited_2984.jpeg",
                "caption": null
            },
            "tags": [],
            "reactions": {
                "reacted": true,
                "total": 2
            },
            "status": "accepted",
            "source": null,
            "remote_addr": "0.0.0.0",
            "created_at": "2016-11-30T21:21:24+00:00",
            "updated_at": "2017-08-02T14:11:26+00:00"
        },
        {
            "id": 3655,
            "signup_id": 5787,
            "northstar_id": "5575e568a59dbf3b7a8b4572",
            "media": {
                "url": "https://s3.amazonaws.com/ds-rogue-qa/uploads/reportback-items/edited_3655.jpeg",
                "caption": "Perhaps you CAN be of some assistance, Bill"
            },
            "tags": [],
            "reactions": {
                "reacted": false,
                "total": 8
            },
            "status": "accepted",
            "source": null,
            "remote_addr": "0.0.0.0",
            "created_at": "2016-02-10T16:19:25+00:00",
            "updated_at": "2017-08-02T14:11:35+00:00"
        }
    ],
    "meta": {
        "pagination": {
            "total": 53,
            "count": 2,
            "per_page": 2,
            "current_page": 1,
            "total_pages": 27,
            "links": {
                "next": "http://rogue.app/api/v2/posts?filter%5Bcampaign_id%5D=1631%2C12&filter%5Bstatus%5D=accepted&filter%5Bexclude%5D=2962%2C3654&limit=2&as_user=559442cca59dbfc&page=2"
            }
        }
    }
}
```

## Create a Post and/or Create/Update a Signup

```
POST /api/v2/posts
```

- **northstar_id**: (string) required.
  The northstar id of the user creating the post.
- **campaign_id**: (int|string) required.
  The ID of the campaign that the user's post is associated with.
- **quantity**: (int).
  The number of reportback nouns verbed.
- **why_participated**: (string).
  The reason why the user participated.
- **caption**: (string).
  Corresponding caption for the post.
- **status**: (string).
  Option to set status upon creation if admin uploads post for user.
- **source**: (string).
  Where the post was submitted from.
- **remote_addr**: (string).
  Will be `0.0.0.0` for all posts in compliance with GDPR.
- **file**: (string) required.
  File string to save of post image.
- **crop_x**: (int).
  The crop x coordinates of the post image if the user cropped the image.
- **crop_y**: (int).
  The crop y coordinates of the post image if the user cropped the image.
- **crop_width** (int).
  The copy width coordinates of the post image if the user cropped the image.
- **crop_height** (int).
  The copy height coordinates of the post image if the user cropped the image.
- **crop_rotate** (int).
  The copy rotate coordinates of the post image if the user cropped the image.
- **dont_send_to_blink** (boolean) optional.
  If included and true, the data for this Post will not be sent to Blink.
- **created_at**: (string) optional.
  `Y-m-d H:i:s` format. When the post was created.
- **updated_at**: (string) optional.
  `Y-m-d H:i:s` format. When the post was last updated.
- **type**: (string).
  The type of post submitted. Must be one of the following types: photo, voter-reg, text, share-social.
- **action**: (string).
  Describes the bucket the action is tied to. A campaign could ask for multiple types of actions throughout the life of the campaign.

Example Response:

```
{
  "data": {
    "id": 340,
    "signup_id": 784,
    "northstar_id": "5571df46a59db12346dsb456d",
    "quantity": "6",
    "media": {
      "url": "https://s3.amazonaws.com/ds-rogue-prod/uploads/reportback-items/edited_214.jpeg",
      "original_image_url": "https://s3.amazonaws.com/ds-rogue-prod/uploads/reportback-items/128-482cab927f6529c7f5e5c4bfd2594186-1501090354.jpeg",
      "caption": "Captioning captions",
    },
    "status": "pending",
    "remote_addr": "0.0.0.0",
    "post_source": "runscope",
    "created_at": "2017-02-15T18:14:58+0000",
    "updated_at": "2017-02-15T18:14:58+0000"
  }
}
```
