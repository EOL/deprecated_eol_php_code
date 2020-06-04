Check your current API credentials:
https://www.flickr.com/services/api/keys/

------------------------------------------------------------------------------------------------
Working Calls without oauth:

https://api.flickr.com/services/rest/?method=flickr.photos.getRecent&api_key=1142baaccbaf8c6cc46f8f4ce26a7135&per_page=10&format=json&nojsoncallback=1
------------------------------------------------------------------------------------------------

Working Calls without oauth:

Used in our Flickr connector:

flickr.photos.getSizes
https://api.flickr.com/services/rest/?&method=flickr.photos.getSizes&api_key=1142baaccbaf8c6cc46f8f4ce26a7135&photo_id=48861925538

flickr.people.getInfo
https://api.flickr.com/services/rest/?&method=flickr.people.getInfo&api_key=1142baaccbaf8c6cc46f8f4ce26a7135&user_id=75374522@N06

flickr.people.getPublicPhotos
http://api.flickr.com/services/rest/?method=flickr.people.getPublicPhotos&api_key=1142baaccbaf8c6cc46f8f4ce26a7135&user_id=75374522@N06&per_page=5

flickr.photos.getInfo
https://api.flickr.com/services/rest/?&method=flickr.photos.getInfo&api_key=1142baaccbaf8c6cc46f8f4ce26a7135&photo_id=48861925538&secret=34933773ab

flickr.groups.pools.getPhotos
https://api.flickr.com/services/rest/?&method=flickr.groups.pools.getPhotos&api_key=1142baaccbaf8c6cc46f8f4ce26a7135&photo_id=48861925538&group_id=806927@N20

flickr.photos.search
https://api.flickr.com/services/rest/?&method=flickr.photos.search&api_key=1142baaccbaf8c6cc46f8f4ce26a7135&photo_id=48861925538&user_id=188734935@N04

------------------------ Removed by Eli, not used anymore.
flickr.auth.getFrob
https://api.flickr.com/services/rest/?&method=flickr.auth.getFrob&api_key=1142baaccbaf8c6cc46f8f4ce26a7135&photo_id=48861925538

flickr.auth.checkToken

flickr.auth.getToken
------------------------------------------------------------------------------------------------