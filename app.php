<?php

/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 7/4/16
 * Time: 3:36 PM
 */

/**
 * Class App
 */
class App
{
    /** @var string */
    protected $entity;

    /** @var string */
    protected $action;

    /** @var array */
    protected $req = [];

    /** @var string */
    protected $ip;

    /** @var mysqli */
    protected $mysqli;

    /** @var array */
    protected $config = [];

    public function __construct(array $srv, array $req)
    {
        $this->config = parse_ini_file(__DIR__ . '/app.ini', true);
        $this->ip = isset($srv['REMOTE_ADDR']) ? $srv['REMOTE_ADDR'] : null;

        if ($tmp = parse_url($srv['REQUEST_URI'])) {
            $path = explode('/', trim($tmp['path'], '/'));

            if (isset($path[0]))
                $this->entity = $path[0];

            if (isset($path[1]))
                $this->action = $path[1];
        }

        $this->req = $req;
    }

    public function __destruct()
    {
        if ($this->mysqli)
            $this->mysqli->close();
    }

    public function run()
    {
        if (!$this->entity)
            return $this->output([
                'err' => 'invalid API entity'
            ]);

        if (!$this->action)
            return $this->output([
                'err' => 'invalid API action'
            ]);

        $method = join('', [
            'action',
            ucfirst($this->entity),
            join('', array_map('ucfirst', explode('-', $this->action)))
        ]);

        if (!method_exists($this, $method))
            return $this->output([
                'err' => 'method not implemented yet'
            ]);

        return $this->$method();
    }

    /**
     * Very(!) simple db manager
     * @param $query
     * @return array|bool
     */
    protected function db($query)
    {
        if (!$this->mysqli) {
            $tmp = new mysqli(
                $this->config['db']['host'],
                $this->config['db']['user'],
                $this->config['db']['pass'],
                $this->config['db']['name']
            );

            if ($tmp->connect_errno)
                return $this->output([
                    'err' => 'internal server error'
                ]);

            $this->mysqli = $tmp;
        }

        $tmp = $this->mysqli->query($query);

        if (is_bool($tmp))
            return $tmp;

        $output = [];
        while ($row = $tmp->fetch_assoc())
            $output[] = $row;

        $tmp->free();

        return $output;
    }

    protected function output(array $response)
    {
        header('Content-type: application/json');
        echo json_encode($response);
        return true;
    }

    /**
     * @return bool
     */
    protected function actionBookmarkCreate()
    {
        if (!isset($this->req['uri']))
            return $this->output([
                'err' => 'invalid bookmark uri'
            ]);

        if (!parse_url($this->req['uri']))
            return $this->output([
                'err' => 'invalid bookmark uri'
            ]);

        $query = join(' ', [
            'SELECT id',
            'FROM bookmark',
            'WHERE uri = \'' . $this->req['uri'] . '\''
        ]);

        if ($bookmarks = $this->db($query))
            return $this->output([
                'id' => $bookmarks[0]['id']
            ]);

        $query = join(' ', [
            'INSERT INTO',
            'bookmark (uri, created_at)',
            'VALUES (\'' . addslashes($this->req['uri']) . '\', NOW())'
        ]);

        if ($this->db($query))
            return $this->output([
                'id' => $this->mysqli->insert_id
            ]);

        return $this->output([
            'err' => 'internal server error'
        ]);
    }

    /**
     * @todo implement transaction
     * @return bool
     */
    protected function actionCommentCreate()
    {
        if (!isset($this->req['id']))
            return $this->output([
                'err' => 'invalid bookmark id'
            ]);

        if (!isset($this->req['text']))
            return $this->output([
                'err' => 'invalid comment text'
            ]);

        $query = join(' ', [
            'SELECT *',
            'FROM bookmark',
            'WHERE id = ' . $this->req['id']
        ]);

        if (!$bookmarks = $this->db($query))
            return $this->output([
                'err' => 'bookmark not found'
            ]);

        $bookmark = $bookmarks[0];

        $query = join(' ', [
            'INSERT INTO',
            'comment (text, ip, created_at)',
            'VALUES (\'' . addslashes($this->req['text']) . '\', \'' . $this->ip . '\', NOW())'
        ]);

        if (!$this->db($query))
            return $this->output([
                'err' => 'internal server error'
            ]);

        $tmp = $this->mysqli->insert_id;

        $query = join(' ', [
            'UPDATE bookmark',
            'SET comments = \'' . join(',', array_merge($bookmark['comments'] ? explode(',', $bookmark['comments']) : [], [$tmp])) . '\'',
            'WHERE id = ' . $bookmark['id']
        ]);

        if ($this->db($query))
            return $this->output([
                'id' => $tmp
            ]);

        return $this->output([
            'err' => 'internal server error'
        ]);
    }

    /**
     * @return bool
     */
    protected function actionCommentUpdate()
    {
        if (!isset($this->req['id']))
            return $this->output([
                'err' => 'invalid comment id'
            ]);

        if (!isset($this->req['text']))
            return $this->output([
                'err' => 'invalid comment text'
            ]);

        $query = join(' ', [
            'SELECT *, UNIX_TIMESTAMP(created_at) AS created_at',
            'FROM comment',
            'WHERE id = ' . $this->req['id']
        ]);

        if (!$comments = $this->db($query))
            return $this->output([
                'err' => 'comment not found'
            ]);

        $comment = $comments[0];

        if ($this->ip != $comment['ip'])
            return $this->output([
                'err' => 'you are not the owner'
            ]);

        if (time() - $comment['created_at'] > 3600)
            return $this->output([
                'err' => 'too late'
            ]);

        $query = join(' ', [
            'UPDATE comment',
            'SET text = \'' . $this->req['text'] . '\'',
            'WHERE id = ' . $this->req['id']
        ]);

        if ($this->db($query))
            return $this->output([
                'ok' => 1
            ]);

        return $this->output([
            'err' => 'internal server error'
        ]);
    }

    /**
     * @todo delete comment id from bookmarks (+transaction if so)
     * @return bool
     */
    protected function actionCommentDelete()
    {
        if (!isset($this->req['id']))
            return $this->output([
                'err' => 'invalid comment id'
            ]);

        $query = join(' ', [
            'SELECT *, UNIX_TIMESTAMP(created_at) as created_at',
            'FROM comment',
            'WHERE id = ' . $this->req['id']
        ]);

        if (!$comments = $this->db($query))
            return $this->output([
                'err' => 'comment not found'
            ]);

        $comment = $comments[0];

        if ($this->ip != $comment['ip'])
            return $this->output([
                'err' => 'you are not the owner'
            ]);

        if (time() - $comment['created_at'] > 3600)
            return $this->output([
                'err' => 'too late'
            ]);

        $query = join(' ', [
            'DELETE FROM',
            'comment WHERE id = ' . $comment['id']
        ]);

        if ($this->db($query))
            return $this->output([
                'ok' => 1
            ]);

        return $this->output([
            'err' => 'internal server error'
        ]);
    }

    /**
     * @return bool
     */
    protected function actionBookmarkGetByUriWithComments()
    {
        if (!isset($this->req['uri']))
            return $this->output([
                'err' => 'invalid bookmark uri'
            ]);

        $query = join(' ', [
            'SELECT *',
            'FROM bookmark',
            'WHERE uri = \'' . $this->req['uri'] . '\''
        ]);

        if (!$bookmarks = $this->db($query))
            return $this->output([
                'err' => 'bookmark not found'
            ]);

        $bookmark = $bookmarks[0];
        $tmp = $bookmark['comments'] ? explode(',', $bookmark['comments']) : [];

        $bookmark['comments'] = [];

        if (!$tmp)
            return $this->output($bookmark);

        $query = join(' ', [
            'SELECT *',
            'FROM comment',
            'WHERE id IN (' . join(',  ', $tmp) . ')'
        ]);

        if (!$comments = $this->db($query))
            return $this->output([
                'err' => 'internal server error'
            ]);

        $bookmark['comments'] = $comments;

        return $this->output($bookmark);
    }

    /**
     * @return bool
     */
    protected function actionBookmarkGetLast()
    {
        $query = join(' ', [
            'SELECT *',
            'FROM bookmark',
            'LIMIT 10'
        ]);

        $bookmarks = $this->db($query);

        if (!is_array($bookmarks))
            return $this->output([
                'err' => 'internal server error'
            ]);

        return $this->output($bookmarks);
    }
}