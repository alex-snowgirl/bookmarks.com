Request uri template:

http://dev.bookmarks.com/<entity>/<action>?<key>=<value>

<entity> and <actions> consists of lowercase words and splits by "-" sign

supported entities (+possible actions):
1) bookmark (create, get-last, get-by-uri-with-comments)
2) comment (create, update, delete)

========================================================
Response format:
JSON

========================================================

EXAMPLES:

http://dev.bookmarks.com/bookmark/create?uri=test1
http://dev.bookmarks.com/bookmark/get-last
http://dev.bookmarks.com/bookmark/get-by-uri-with-comments?uri=test5
http://dev.bookmarks.com/comment/create?id=5&text=test_comment7
http://dev.bookmarks.com/comment/update?id=13&text=test_comment13_updated
http://dev.bookmarks.com/comment/delete?id=11