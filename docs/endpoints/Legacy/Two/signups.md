## Signups

## Create a Signup

```
POST /api/v2/signups
```

- **northstar_id**: (string) required.
  The northstar id of the user signing up.
- **campaign_id**: (int|string) required.
  The ID of the campaign the user is signing up for.
- **quantity**: (int) optional.
  The approved number of reportback nouns verbed.
- **quantity_pending**: (int) optional.
  The pending number of reportback nouns verbed.
- **why_participated**: (string) optional.
  The reason why the user participated.
- **source**: (string) optional (for migration purposes, there are signups on prod with no source).
  The source of the signup.
- **details**: (string) optional
  Details to be added to the "details" column on the signup, such as signed up to receive affiliate messaging.
- **dont_send_to_blink** (boolean) optional.
  If included and true, the data for this Signup will not be sent to Blink.
- **created_at**: (string) optional.
  `Y-m-d H:i:s` format. When the signup was created.
- **updated_at**: (string) optional.
  `Y-m-d H:i:s` format. When the signup was last updated.

  You may optionally include reportback photo(s)

Example request body:

```
[
  "northstar_id" => "5639066ba59dbfe6598b4567"
  "campaign_id" => "6"
  "campaign_run_id" => "1740"
  "quantity" => "200"
  "source" => "the-fox-den"
  "why_participated" => "bcuz I luv endpointz"
  "created_at" => "1991-10-21 16:50:36"
  "updated_at" => "1991-10-21 16:50:36"
  "photo" => array:2 [
    0 => array:7 [
      "source" => "photo-source"
      "remote_addr" => "0.0.0.0"
      "caption" => "LOOK AT THAT CAT"
      "event_type" => "post_photo"
      "northstar_id" => "5639066ba59dbfe6598b4567"
      "do_not_forward" => "TRUE"
      "file" => UploadedFile {#169
        -test: false
        -originalName: "tongue-cat.jpg"
        -mimeType: "image/jpeg"
        -size: 61423
        -error: 0
        path: "/tmp"
        filename: "phppWmMAx"
        basename: "phppWmMAx"
        pathname: "/tmp/phppWmMAx"
        extension: ""
        realPath: "/tmp/phppWmMAx"
        aTime: 2017-01-19 20:20:12
        mTime: 2017-01-19 20:20:12
        cTime: 2017-01-19 20:20:12
        inode: 1700653
        size: 61423
        perms: 0100600
        owner: 900
        group: 900
        type: "file"
        writable: true
        readable: true
        executable: false
        file: true
        dir: false
        link: false
      }
    ]
    1 => array:7 [
      "source" => "second-source"
      "remote_addr" => "0.0.0.0"
      "caption" => "lil chickens"
      "event_type" => "post_photo"
      "northstar_id" => "5639066ba59dbfe6598b4567"
      "do_not_forward" => "TRUE"
      "file" => UploadedFile {#171
        -test: false
        -originalName: "chickens.png"
        -mimeType: "image/png"
        -size: 612306
        -error: 0
        path: "/tmp"
        filename: "phpfhZAf7"
        basename: "phpfhZAf7"
        pathname: "/tmp/phpfhZAf7"
        extension: ""
        realPath: "/tmp/phpfhZAf7"
        aTime: 2017-01-19 20:20:12
        mTime: 2017-01-19 20:20:12
        cTime: 2017-01-19 20:20:12
        inode: 1700654
        size: 612306
        perms: 0100600
        owner: 900
        group: 900
        type: "file"
        writable: true
        readable: true
        executable: false
        file: true
        dir: false
        link: false
      }
    ]
  ]
]
```

Response: The signup and associated events and posts.

Example response:

```
{
  "data": {
    "signup_id": 160,
    "northstar_id": "5571df46a59db12346dsb456d",
    "campaign_id": "6",
    "campaign_run_id": "1740",
    "quantity": null,
    "quantity_pending": "200",
    "why_participated": "bcuz I luv endpointz",
    "signup_source": "phoenix-web",
    "details": "affiliate-messaging",
    "created_at": "2017-01-19T19:11:14+0000",
    "updated_at": "2017-01-19T19:11:14+0000",
    "posts": {
      "data": [
        {
          "id": 340,
          "signup_id": 160,
          "northstar_id": "5571df46a59db12346dsb456d",
          "media": {
            "url": "https://s3.amazonaws.com/ds-rogue-prod/uploads/reportback-items/18-1487182498.jpeg",
            "caption": "Captioning captions",
          },
          "status": "pending",
          "remote_addr": "0.0.0.0",
          "post_source": "runscope",
          "created_at": "2017-02-15T18:14:58+0000",
          "updated_at": "2017-02-15T18:14:58+0000",
        },
      ],
    },
  }
}
```
