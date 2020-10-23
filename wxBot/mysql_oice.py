#!/usr/bin/env python
# coding: utf-8

import MySQLdb

db_host = '127.0.0.1'
db_user = 'oice'
db_pwd = 'c4oSRpVxwsZcQ6GQ'
db = 'oice'
charset='utf8mb4'

# 创建数据库连接
def get_conn():
    return MySQLdb.connect(
        host = db_host,
        port = 3306,
        user= db_user,
        passwd = db_pwd,
        db = db,
        charset=charset
        )

# 关闭数据库连接
def close_conn(conn):
    conn.commit()
    conn.close()

# 执行非查询SQL语句
def execute_sql(sql, param = ()):
    conn = get_conn()
    cur = conn.cursor()
    
    result = cur.execute(sql, param)
    
    cur.close()
    close_conn(conn)
    return result

# 获取单条数据
def get_one(sql, param = ()):
    conn = get_conn()
    cur = conn.cursor()
    
    result = cur.execute(sql, param)
    if result > 0:
        rec = cur.fetchone()
    else:
        rec = None
        
    cur.close()
    close_conn(conn)
    
    return rec

# 获取数据列表
def get_list(sql, param = ()):
    conn = get_conn()
    cur = conn.cursor()
    
    result = cur.execute(sql, param)
    dataList = cur.fetchmany(result)
    
    cur.close()
    close_conn(conn)
    
    return dataList

def main():
    test = get_one('select * from tbl_verify LIMIT 0, 3')
    print test

if __name__ == '__main__':
    main()