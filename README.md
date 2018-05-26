# ChronoSubs

[ChronoSubs](https://www.chronosubs.com/) lists videos from your YouTube subscriptions in chronological order.

## Method

Using the YouTube API and Google OAuth, we fetch your list of subscriptions on your YouTube account, then fetch the videos from each channel which you follow.
 
## Reason

[YouTube is "currently experimenting" with non-chronological subscription feeds](https://twitter.com/TeamYouTube/status/999150003863085056) and there was a bit of a backlash from some users about that.
 
## Support

This was thrown together in a couple days; no support should be expected at this time.

## Privacy/Security

This project does not store any OAuth access tokens, and forgets your subscriptions after sending them to your browser. Nothing gets written to disk except for standard web server logs, which are rotated frequently. 

## License

Copyright 2018 Gerard Dombroski

Licensed under the Apache License, Version 2.0 (the "License");
you may not use these files except in compliance with the License.
You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
