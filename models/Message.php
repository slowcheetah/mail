<?php

namespace humhub\modules\mail\models;


use humhub\modules\content\widgets\richtext\RichText;
use humhub\modules\mail\Module;
use Yii;
use humhub\components\ActiveRecord;
use humhub\modules\user\models\User;

/**
 * This class represents a single conversation.
 *
 * The followings are the available columns in table 'message':
 * @property integer $id
 * @property string $title
 * @property string $created_at
 * @property integer $created_by
 * @property string $updated_at
 * @property integer $updated_by
 * @property-read  User $originator
 * @property-read MessageEntry $lastEntry
 *
 * The followings are the available model relations:
 * @property MessageEntry[] $messageEntries
 * @property User[] $users
 *
 * @package humhub.modules.mail.models
 * @since 0.5
 */
class Message extends ActiveRecord
{

    /**
     * @return string the associated database table name
     */
    public static function tableName()
    {
        return 'message';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return [
            [['created_by', 'updated_by'], 'integer'],
            [['title'], 'string', 'max' => 255],
            [['created_at', 'updated_at'], 'safe'],
        ];
    }

    public function getEntryUpdates($from = null)
    {
        $query = $this->hasMany(MessageEntry::class, ['message_id' => 'id']);
        $query->addOrderBy(['created_at' => SORT_ASC]);

        if ($from) {
            $query->andWhere(['>', 'message_entry.id', $from]);
        }

        return $query;
    }

    /**
     * @param int|null $from
     * @return MessageEntry[]
     */
    public function getEntryPage($from = null)
    {
        $query = $this->getEntries();
        $query->addOrderBy(['created_at' => SORT_DESC]);

        if ($from) {
            $query->andWhere(['<', 'message_entry.id', $from]);
        }

        $module = Module::getModuleInstance();
        $limit = $from ? $module->conversationUpdatePageSize : $module->conversationInitPageSize;
        $query->limit($limit);

        return array_reverse($query->all());
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEntries()
    {
        return $this->hasMany(MessageEntry::class, ['message_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAuthor()
    {
        return $this->hasOne(User::class, ['id' => 'created_by']);
    }

    /**
     * @param User $user
     * @return \yii\db\ActiveQuery
     * @throws \yii\base\InvalidConfigException
     */
    public function isModerator(User $user)
    {
        return $this->hasOne(User::class, ['id' => 'user_id'])
            ->viaTable('user_message', ['message_id' => 'id', 'user_id' => $user->id, 'is_originator' => 1]);
    }

    /**
     * @return \yii\db\ActiveQuery
     * @throws \yii\base\InvalidConfigException
     */
    public function getUsers()
    {
        return $this->hasMany(User::class, ['id' => 'user_id'])
            ->viaTable('user_message', ['message_id' => 'id']);
    }

    /**
     * @param null $userId
     * @return UserMessage|null
     */
    public function getUserMessage($userId = null)
    {
        if (!$userId) {
            $userId = Yii::$app->user->id;
        }

        return UserMessage::findOne([
            'user_id' => $userId,
            'message_id' => $this->id
        ]);
    }

    /**
     * @param $user
     * @return bool
     */
    public function isParticipant($user)
    {
        foreach ($this->users as $participant) {
            if ($participant->guid === $user->guid) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOriginator()
    {
        return $this->hasOne(User::class, ['id' => 'created_by']);
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return array(
            'id' => 'ID',
            'title' => Yii::t('MailModule.base', 'Title'),
            'created_at' => Yii::t('MailModule.base', 'Created At'),
            'created_by' => Yii::t('MailModule.base', 'Created By'),
            'updated_at' => Yii::t('MailModule.base', 'Updated At'),
            'updated_by' => Yii::t('MailModule.base', 'Updated By'),
        );
    }

    /**
     * Returns the last message of this conversation
     * @return MessageEntry
     */
    public function getLastEntry()
    {
        return MessageEntry::find()->where(['message_id' => $this->id])->orderBy('created_at DESC')->limit(1)->one();
    }

    /**
     * @param bool $includeMe
     * @return \yii\web\IdentityInterface|null
     * @throws \Throwable
     * @throws \yii\base\InvalidConfigException
     */
    public function getLastActiveParticipant($includeMe = false)
    {
        $query = MessageEntry::find()->where(['message_id' => $this->id])->orderBy('created_at DESC')->limit(1);

        if (!$includeMe) {
            $query->andWhere(['<>', 'user_id', Yii::$app->user->id]);
        }

        $entry = $query->one();

        $user = $entry ? $entry->user : $this->getUsers()->andWhere(['<>', 'user.id', Yii::$app->user->id])->one();

        return $user ?: Yii::$app->user->getIdentity();
    }

    /**
     * Deletes message entry by given Id
     * If it's the last entry, the whole message will be deleted.
     *
     * @param MessageEntry $entry
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function deleteEntry($entry)
    {
        if ($entry->message->id == $this->id) {
            if($this->getEntries()->count() > 1) {
                $entry->delete();
            } else {
                $this->delete();
            }
        }
    }

    /**
     * User leaves a message
     *
     * If it's the last user, the whole message will be deleted.
     *
     * @param int $userId
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function leave($userId)
    {
        $userMessage = UserMessage::findOne([
            'message_id' => $this->id,
            'user_id' => $userId
        ]);

        if(!$userMessage) {
            return;
        }

        if (count($this->users) > 1) {
            $userMessage->delete();
        } else {
            $this->delete();
        }
    }

    /**
     * Marks a message as seen for given userId
     *
     * @param int $userId
     */
    public function seen($userId)
    {
        // Update User Message Entry
        $userMessage = UserMessage::findOne(array(
            'user_id' => $userId,
            'message_id' => $this->id
        ));
        if ($userMessage !== null) {
            $userMessage->last_viewed = date('Y-m-d G:i:s');
            $userMessage->save();
        }
    }

    /**
     * Deletes a message, including all dependencies.
     */
    public function delete()
    {
        foreach (MessageEntry::findAll(array('message_id' => $this->id)) as $messageEntry) {
            $messageEntry->delete();
        }

        foreach (UserMessage::findAll(array('message_id' => $this->id)) as $userMessage) {
            $userMessage->delete();
        }

        parent::delete();
    }

    public function getPreview()
    {
        if(!$this->lastEntry) {
            return 'No message found';
        }

        return RichText::preview($this->lastEntry->content, 80);
    }

    /**
     * @param User $recipient
     * @return bool
     */
    public function addRecepient(User $recipient, $originator = false)
    {
        $userMessage = new UserMessage([
            'message_id' => $this->id,
            'user_id' => $recipient->id
        ]);

        if ($originator) {
            $userMessage->is_originator = 1;
            $userMessage->last_viewed = date('Y-m-d G:i:s');
        }

        return $userMessage->save();

    }

    public function canEdit()
    {
        return (($this->originator->id == Yii::$app->user->id) || Yii::$app->user->isAdmin());
    }

    public function canEditTitle()
    {
        return (($this->originator->id == Yii::$app->user->id) || Yii::$app->user->isAdmin() || $this->isModerator(Yii::$app->user->id));
    }
}
