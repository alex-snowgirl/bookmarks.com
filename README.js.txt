Request uri template:

http://dev.bookmarks.com/<entity>/<action>?<key>=<value>

<entity> and <actions> consists of lowercase words and splits by "-" sign

supported entities (+possible actions):
1) bookmark (create, get-last, get-by-uri-with-comments)
2) comment (create, update, delete)

========================================================
Response format:
JSON

Response with error contains "err" key with error message

========================================================

EXAMPLES:

http://dev.bookmarks.com/bookmark/create?uri=test1
http://dev.bookmarks.com/bookmark/create?uri=test2
http://dev.bookmarks.com/bookmark/create?uri=test3
http://dev.bookmarks.com/bookmark/get-last
http://dev.bookmarks.com/comment/create?id=1&text=test_comment_1_1
http://dev.bookmarks.com/comment/create?id=1&text=test_comment_1_2
http://dev.bookmarks.com/comment/create?id=2&text=test_comment_2_1
http://dev.bookmarks.com/comment/create?id=3&text=test_comment_3_1
http://dev.bookmarks.com/comment/update?id=3&text=test_comment_3_1_updated
http://dev.bookmarks.com/bookmark/get-by-uri-with-comments?uri=test3
http://dev.bookmarks.com/comment/delete?id=1
http://dev.bookmarks.com/comment/delete?id=2