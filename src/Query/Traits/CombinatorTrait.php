<?php
declare(strict_types = 1);

namespace SimpleCrud\Query\Traits;

use SimpleCrud\Row;
use SimpleCrud\RowCollection;
use SimpleCrud\Table;

trait CombinatorTrait
{
    private function combine(array $result, $relations)
    {
        $table1 = $this->table;
        $table2 = $relations->getTable();

        //Has one
        if ($table1->getJoinField($table2)) {
            if ($this->one) {
                if ($relations instanceof Row) {
                    return self::combineHasOneRowWithRow($this->table, $result, $relations);
                }

                return self::combineHasOneRowWithRowCollection($this->table, $result, $relations);
            }

            if ($relations instanceof Row) {
                return self::combineHasOneRowCollectionWithRow($this->table, $result, $relations);
            }

            return self::combineHasOneRowCollectionWithRowCollection($this->table, $result, $relations);
        }

        //Has many
        if ($table2->getJoinField($table1)) {
            if ($this->one) {
                if ($relations instanceof Row) {
                    return self::combineHasManyRowWithRow($this->table, $result, $relations);
                }

                return self::combineHasManyRowWithRowCollection($this->table, $result, $relations);
            }

            if ($relations instanceof Row) {
                return self::combineHasManyRowCollectionWithRow($this->table, $result, $relations);
            }

            return self::combineHasManyRowCollectionWithRowCollection($this->table, $result, $relations);
        }

        //Has many to many
        if ($this->one) {
            if ($relations instanceof Row) {
                return self::combineHasManyToManyRowWithRow($this->table, $result, $relations);
            }

            return self::combineHasManyToManyRowWithRowCollection($this->table, $result, $relations);
        }

        if ($relations instanceof Row) {
            return self::combineHasManyToManyRowCollectionWithRow($this->table, $result, $relations);
        }

        return self::combineHasManyToManyRowCollectionWithRowCollection($this->table, $result, $relations);
    }

    //remove?
    private static function combineHasOneRowWithRow(
        Table $table,
        array $comment,
        Row $post
    ) {
        $comment = $table->create($comment, true);
        $comment->link($post, false);

        return $comment;
    }

    //remove?
    private static function combineHasOneRowWithRowCollection(
        Table $table,
        array $comment,
        RowCollection $posts
    ) {
        return $table->create($comment, true);
    }

    private static function combineHasOneRowCollectionWithRow(
        Table $table,
        array $comments,
        Row $post
    ) {
        $comments = $table->createCollection($comments, true);

        foreach ($comments as $comment) {
            $comment->link($post);
        }

        return $comments;
    }

    private static function combineHasOneRowCollectionWithRowCollection(
        Table $table,
        array $comments,
        RowCollection $posts
    ) {
        $comments = $table->createCollection($comments, true);

        //$posts->cache($comments);
        //$comments->cache($posts);

        $foreignKey = $posts->getTable()->getForeignKey();

        foreach ($comments as $comment) {
            $id = $comment->$foreignKey;

            $comment->link($posts[$id]);
        }

        foreach ($posts as $post) {
            $post->defineLink($table);
        }

        //foreach ($map as $id => $value) {
        //    $posts[$id]->cache($comments->getTable()->createCollection($value));
        //}

        return $comments;
    }

    private static function combineHasManyRowWithRow(
        Table $table,
        array $post,
        Row $comment
    ) {
        $post = $table->create($post, true);
        $comment->cache($post);

        return $post;
    }

    private static function combineHasManyRowWithRowCollection(
        Table $table,
        array $post,
        RowCollection $comments
    ) {
        $post = $table->create($post, true);

        $post->cache($comments);

        foreach ($comments as $comment) {
            $comment->cache($post);
        }

        return $post;
    }

    private static function combineHasManyRowCollectionWithRow(
        Table $table,
        array $posts,
        Row $comment
    ) {
        $posts = $table->createCollection($posts, true);

        $foreignKey = $posts->getTable()->getForeignKey();
        $comment->cache($posts[$comment->$foreignKey]);
        $posts[$comment->$foreignKey]->cache($comment);

        return $posts;
    }

    private static function combineHasManyRowCollectionWithRowCollection(
        Table $table,
        array $posts,
        RowCollection $comments
    ) {
        $posts = $table->createCollection($posts, true);

        $posts->cache($comments);
        $comments->cache($posts);

        $foreignKey = $posts->getTable()->getForeignKey();
        $map = [];

        foreach ($comments as $comment) {
            $id = $comment->$foreignKey;

            $comment->cache($posts[$id]);

            if (!isset($map[$id])) {
                $map[$id] = [];
            }

            $map[$id][] = $comment;
        }

        foreach ($map as $id => $value) {
            $posts[$id]->cache($comments->getTable()->createCollection($value));
        }

        return $posts;
    }

    private static function combineHasManyToManyRowWithRow(
        Table $table,
        array $post,
        Row $category
    ) {
        $foreignKey = $category->getTable()->getForeignKey();

        unset($post[$foreignKey]);

        return $table->create($post, true);
    }

    private static function combineHasManyToManyRowWithRowCollection(
        Table $table,
        array $post,
        RowCollection $categories
    ) {
        $foreignKey = $categories->getTable()->getForeignKey();

        unset($post[$foreignKey]);

        return $table->create($post, true);
    }

    private static function combineHasManyToManyRowCollectionWithRow(
        Table $table,
        array $posts,
        Row $category
    ) {
        $foreignKey = $category->getTable()->getForeignKey();

        foreach ($posts as &$post) {
            unset($post[$foreignKey]);
        }

        return $table->createCollection($posts, true);
    }

    private static function combineHasManyToManyRowCollectionWithRowCollection(
        Table $table,
        array $posts,
        RowCollection $categories
    ) {
        $foreignKey = $categories->getTable()->getForeignKey();

        $uniquePosts = [];
        $mapCategories = [];
        $mapPosts = [];

        foreach ($posts as $post) {
            $postId = $post['id'];
            $categoryId = $post[$foreignKey];

            if (!isset($uniquePosts[$postId])) {
                unset($post[$foreignKey]);
                $uniquePosts[$postId] = $table->create($post, true);
            }

            if (!isset($mapPosts[$postId])) {
                $mapPosts[$postId] = [];
            }

            $mapPosts[$postId][] = $categories[$categoryId];

            if (!isset($mapCategories[$categoryId])) {
                $mapCategories[$categoryId] = [];
            }

            $mapCategories[$categoryId][] = $uniquePosts[$postId];
        }

        $posts = $table->createCollection($uniquePosts);
        $posts->cache($categories);
        $categories->cache($posts);

        $categoryTable = $categories->getTable();

        foreach ($mapPosts as $id => $value) {
            $posts[$id]->cache($categoryTable->createCollection($value));
        }

        foreach ($mapCategories as $id => $value) {
            //$categories[$id]->cache($table->createCollection($posts));
        }

        return $posts;
    }
}
