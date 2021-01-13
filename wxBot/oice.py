#!/usr/bin/env python
# coding: utf-8
#

import sys
import traceback
import threading
import time
import re
import mysql_oice
import urllib
import urllib2
import json
import cgi
import datetime

from traceback import format_exc
from wxbot import *

reload(sys)  
sys.setdefaultencoding('utf8')

# 管理员名单
admin_user = [u'芬达',u'萝卜']
# 退出命令
exit_cmd = [u'exit', u'退出', u'退下', u'走开', u'关闭', u'关掉', u'滚', u'滚开']
# 休眠命令
stop_cmd = [u'sleep', u'休息', u'休眠']
# 唤醒命令
start_cmd = [u'wake up', u'wakeup', u'唤醒', u'启动', u'工作', u'醒醒']
# 群禁言命令
group_wait_cmd = [u'wait', u'shutup', u'shut up', u'安静', u'闭嘴']
# 群活跃命令
group_active_cmd = [u'active', u'说话', u'活跃', u'出来', u'回来', u'活跃一下']

class oiceBot(WXBot):
    def __init__(self):
        WXBot.__init__(self)
        self.tuling_key = "18b26bf07de548aabfe86d14576d9db4"
        self.uuid = ''
        self.oice_id = ''
        self.counter = 720
        self.task_counter = 2
        self.sleep = '0'
        self.active_group = []
    
    # 是否管理员账号
    def is_admin(self, uid):
        if uid == self.oice_id:
            return True
        
        for account in self.contact_list:
            if uid == account['UserName']:
                for u in admin_user:
                    if u == account['RemarkName'] or u == account['NickName']:
                        return True
                return False
        return False
    
    # 判断群消息是否@我（0 没有@任何人, 1 @某人, 2 @我）
    def is_at_me(self, gid, msg):
        result = 0
        my_names = self.get_group_member_name(gid, self.my_account['UserName'])
        if my_names is None:
            my_names = {}
        if 'NickName' in self.my_account and self.my_account['NickName']:
            my_names['nickname2'] = self.my_account['NickName']
        if 'RemarkName' in self.my_account and self.my_account['RemarkName']:
            my_names['remark_name2'] = self.my_account['RemarkName']
            
        for detail in msg:
            if detail['type'] == 'at':
                result = 1
                for k in my_names:
                    if my_names[k] and my_names[k] == detail['value']:
                        result = 2
        return result
    
    # 获取公众号号id
    def get_public_id(self, name):
        if name == '':
            return None
        name = self.to_unicode(name)
        for public in self.public_list:
            if 'RemarkName' in public and public['RemarkName'] == name:
                return public['UserName']
            elif 'NickName' in public and public['NickName'] == name:
                return public['UserName']
            elif 'DisplayName' in public and public['DisplayName'] == name:
                return public['UserName']
        return ''
    
    # 图灵机器自动回复
    def tuling_auto_reply(self, uid, msg):
        if self.tuling_key:
            url = "http://www.tuling123.com/openapi/api"
            user_id = uid.replace('@', '')[:30]
            body = {'key': self.tuling_key, 'info': msg.encode('utf8'), 'userid': user_id}
            r = requests.post(url, data=body)
            respond = json.loads(r.text)
            result = ''
            if respond['code'] == 100000:
                result = respond['text'].replace('<br>', '  ')
                result = result.replace(u'\xa0', u' ')
            elif respond['code'] == 200000:
                result = respond['url']
            elif respond['code'] == 302000:
                for k in respond['list']:
                    result = result + u"【" + k['source'] + u"】 " +\
                        k['article'] + "\t" + k['detailurl'] + "\n"
            else:
                result = respond['text'].replace('<br>', '  ')
                result = result.replace(u'\xa0', u' ')
            return result
        else:
            return u'知道啦'
    
    # 执行指令
    def exec_command(self, msg, user, is_at = 0, group_id = '', tuling_replay = False):
        answer = ''
        at_name = ''
        
        if group_id != '' and is_at != 3 and user['name'] != 'unknown':
            at_name = '@%s' % self.replace_emoji_code(user['name'])

        # 管理员指令(在群里需要AT机器人或机器人发个自己)
        if is_at == 3 or (self.is_admin(user['id']) and (is_at == 2 or group_id == '')):
            for ext in exit_cmd:
                # 退出
                if ext == msg:
                    answer = u'小生告退。'
                    self.status = 'wait4loginout'
                    break
            if answer == '':
                # 休眠
                for stop in stop_cmd:
                    if stop == msg:
                        answer = u'好吧，我休息了。'
                        self.sleep = '1'
                        break
            if answer == '':
                # 唤醒
                for start in start_cmd:
                    if start == msg:
                        answer = u'听候吩咐。'
                        self.sleep = '0'
                        break
            if answer == '' and group_id != '':
                # 在群里安静
                for wait in group_wait_cmd:
                    if wait == msg:
                        answer = u'好吧，我不说话了。'
                        self.active_group.remove(group_id)
                        break
            if answer == '' and group_id != '':
                # 在群里活跃
                for active in group_active_cmd:
                    if active == msg:
                        answer = u'我来了！'
                        find = False
                        for group in self.active_group:
                            if group == group_id:
                                find = True
                        if find == False:
                            self.active_group.append(group_id)
                        break
            # if answer == '':
            #     matchs = re.match(r'给(([\S ]+)\)转发 ([\S \n\r\t]+)$', msg, re.M)
            #     if matchs:
            #         self.send_msg(matchs.group(1), matchs.group(2))
            #         return
        if answer == '' and self.sleep == '0':
            if group_id != '':
                if tuling_replay:
                    answer = self.tuling_auto_reply(user['id'], msg)
            elif tuling_replay:
                answer = self.tuling_auto_reply(user['id'], msg)
        
        # 组装回复消息
        if answer != '':
            if group_id != '':
                if at_name != '':
                    answer = '%s %s' % (at_name, answer)
                self.send_msg_by_uid(answer, group_id)
            else:
                self.send_msg_by_uid(answer, user['id'])
    
    # 处理任务
    def do_task(self):
        try:
            sql = "select id,uid,task,level,cycle_hour,start_hour,end_hour,task_time from tbl_robot_task where uid = '%s' and status = 0 and (task_time is null or task_time <= NOW()) and (start_hour is null or HOUR(NOW()) >= start_hour) and (end_hour is null or HOUR(NOW()) < end_hour) order by level desc,id limit 1" % self.my_account['UserName']
            task = mysql_oice.get_one(sql)
            if task is not None:
                isDone = False
                # 下线
                if task[2] == 'OFFLINE':
                    self.status = 'wait4loginout'
                    isDone = True
                # 休眠
                elif task[2] == 'SLEEP':
                    self.sleep = '1'

                    sql = "update tbl_robot set status = 2 where uid = '%s'" % (self.my_account['UserName'])
                    mysql_oice.execute_sql(sql)

                    isDone = True
                # 唤醒
                elif task[2] == 'WEAKUP':
                    self.sleep = '0'

                    sql = "update tbl_robot set status = 1 where uid = '%s'" % (self.my_account['UserName'])
                    mysql_oice.execute_sql(sql)

                    isDone = True
                elif self.sleep == '0':
                    # 通过ID转发消息
                    matchs = re.match(r'^TURN\|(@\S+)\|([\S \n\r\t]+)$', task[2], re.M)
                    if matchs:
                        content = matchs.group(2).replace('\\n', '\n').replace('[F]', u'🚺').replace('[M]', u'🚹')
                        self.send_msg_by_uid(content, matchs.group(1))
                        isDone = True
                    else:
                        # 通过昵称转发消息
                        matchs = re.match(r'^TURN\|([\S ]+)\|([\S \n\r\t]+)$', task[2], re.M)
                        if matchs:
                            content = matchs.group(2).replace('\\n', '\n').replace('[F]', u'🚺').replace('[M]', u'🚹')
                            self.send_msg(matchs.group(1), content)
                            isDone = True
                        else:
                            #TODO:加用户进群
                            matchs = re.match(r'^ADD_GROUP\|([\S ]+)\|([\S ]+)$', task[1])
                            if matchs:
                                pass
                if isDone == True:                  
                    sql = "update tbl_robot_task set status = 1 where id = %s" % task[0]
                    mysql_oice.execute_sql(sql)

                # 如果是循环定时任务则自动添加下一次任务
                if int(task[4]) > 0:
                    now = datetime.datetime.now()
                    task_time = now + datetime.timedelta(hours=int(task[4]))
                    task_time_str = task_time.strftime('%Y-%m-%d %H:%M:%S')
                    mysql_oice.execute_sql("insert into tbl_robot_task (uid,task,level,cycle_hour,start_hour,end_hour,task_time,status) values (%s,%s,%s,%s,%s,%s,%s,%s)",
                        (task[1],task[2],task[3],task[4],task[5],task[6],task_time_str,0))
        except Exception as e:
            print '[ERROR] ' + e.message, traceback.format_exc(), time.strftime('%Y-%m-%d %H:%M:%S',time.localtime(time.time()))
        
    # 处理消息
    def handle_msg_all(self, msg):
        # 自己发的消息
        if msg['msg_type_id'] == 1 and msg['content']['type'] == 0:
            self.exec_command(msg['content']['data'], msg['user'], 3, msg['to_user_id'])
        # 群消息
        elif msg['msg_type_id'] == 3 and msg['content']['type'] == 0:
            # @是否AT机器人
            is_at = self.is_at_me(msg['user']['id'], msg['content']['detail'])
            is_active_group = False
            if is_at == 0:
                for group in self.active_group:
                    if group == msg['user']['id']:
                        is_active_group = True
            if is_at == 2 or is_active_group:
                self.exec_command(msg['content']['desc'], msg['content']['user'], is_at, msg['user']['id'])   
        # 联系人消息
        elif msg['msg_type_id'] == 4 and msg['content']['type'] == 0:
            self.exec_command(msg['content']['data'], msg['user'])
        # 公众号消息
        elif msg['msg_type_id'] == 5 and msg['content']['type'] == 0 and msg['user']['id'] == self.oice_id:
            self.exec_command(msg['content']['data'], msg['user'], 0, '')

    # 定时动作
    def schedule(self):
        if self.counter >= 720:
            self.counter = 0
            if self.oice_id != '':
                cur = datetime.datetime.now()
                if cur.hour >= 6 and cur.hour < 22:
                    self.send_msg_by_uid(u'[签到]' + self.my_account['UserName'], self.oice_id)
                else:
                    self.send_msg_by_uid(u'夜间值班 ' + cur.strftime('%Y-%m-%d %H:%M:%S'), self.oice_id)

        self.counter = self.counter + 1

        if self.task_counter >= 2:
            self.task_counter = 0
            self.do_task()

        self.task_counter = self.task_counter + 1
    
    # 机器人初始化事件
    def handle_init(self):
        print '[INFO] Handle init'
        
        # 获取公众号ID,定时发送消息,保持接受公众号的消息推送
        if self.oice_id == '':
            self.oice_id = self.get_public_id(u'商办云信息')
            
        try:
            #保存在线机器人信息
            mysql_oice.execute_sql('insert into tbl_robot (uid, name, avatar, status) values (%s,%s,%s,%s)', 
                (self.my_account['UserName'], self.my_account['NickName'], self.my_account['HeadImgUrl'], 1))
            
            #保存机器人的群列表
            sql = 'insert into tbl_robot_contact (type, uid, cid, name, avatar) values (%s,%s,%s,%s,%s)'
            for group in self.group_list:
                name = ''
                if group['RemarkName'] != '':
                    name = group['RemarkName']
                elif group['NickName'] != '':
                    name = group['NickName']
                elif group['DisplayName'] != '':
                    name = group['DisplayName']
                mysql_oice.execute_sql(sql, (0, self.my_account['UserName'], group['UserName'], self.replace_emoji_code(self.to_unicode(name)), group['HeadImgUrl']))

            #保存机器人的联系人列表
            for contact in self.contact_list:
                name = ''
                if contact['RemarkName'] != '':
                    name = contact['RemarkName']
                elif contact['NickName'] != '':
                    name = contact['NickName']
                elif contact['DisplayName'] != '':
                    name = contact['DisplayName']
                mysql_oice.execute_sql(sql, (1, self.my_account['UserName'], contact['UserName'], self.replace_emoji_code(self.to_unicode(name)), contact['HeadImgUrl']))

        except Exception as e:
            print '[ERROR] ' + e.message, traceback.format_exc(), time.strftime('%Y-%m-%d %H:%M:%S',time.localtime(time.time()))
    
    #机器人退出事件    
    def handle_exit(self):
        print '[INFO] Handle exit', time.strftime('%Y-%m-%d %H:%M:%S',time.localtime(time.time()))
        
        try:
            sql = "update tbl_robot set status = 0 where uid = '%s'" % (self.my_account['UserName'])
            mysql_oice.execute_sql(sql)
            
            sql = "delete from tbl_robot_contact where uid = '%s'" % (self.my_account['UserName'])
            mysql_oice.execute_sql(sql)

            sql = "delete from tbl_robot_task where uid = '%s'" % (self.my_account['UserName'])
            mysql_oice.execute_sql(sql)
        except Exception as e:
            print '[ERROR] ' + e.message, traceback.format_exc(), time.strftime('%Y-%m-%d %H:%M:%S',time.localtime(time.time()))

    # 获得 emoji 表情字符
    def emoji_char(self, matched):
        s = '\u' + matched.group('code')
        return s.encode('unicode_escape')
        #return unichr(int(matched.group('code'), 16))

    # 替换 emoji 表情字符
    def replace_emoji_code(self, input_str):
        try:
            return re.sub(r'<span class="emoji emoji(?P<code>[a-f\d]+)"></span>', self.emoji_char, input_str)
        except Exception as e:
            print '[ERROR] ' + e.message, traceback.format_exc(), time.strftime('%Y-%m-%d %H:%M:%S',time.localtime(time.time()))
            return input_str

class botThread (threading.Thread):
    def __init__(self, bot):
        threading.Thread.__init__(self)
        self.bot = bot
    def run(self):
        self.bot.run()
    
def run():
    bot = oiceBot()
    bot.DEBUG = False
    bot.conf['qr'] = ''
    botThread(bot).start()
    while True:
        if bot.uuid != '':
            url = 'https://login.wx.qq.com/qrcode/%s' % bot.uuid
            return url
        else:
            time.sleep(2)
