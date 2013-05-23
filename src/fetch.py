#!/usr/bin/python
#-*-coding: utf8

#
#   Author:  Cliff Chao-kuan Lu <clifflu@gmail.com>
#   License: MIT License
#

# 
# Resources:
#   AWS EC2 instance types:
#       - http://aws.amazon.com/ec2/instance-types/
#       - http://aws.amazon.com/ec2/instance-types/instance-details/
#   Source JSON files:
#       - http://aws.amazon.com/ec2/pricing/ , xhr calls
#
#
# todo: currency other than USD
#

import os
import json
import sys
import re

#
# Util
#

def lookup_dict(key, tbl):
    if key in tbl.keys():
        return tbl[key]
    return key

def aws_url(fn):
    return CONFIG['filelist']['prefix'] + fn + CONFIG['filelist']['appendix']

def local_fn(fn):
    return os.path.join(PATH['TMP'], fn + CONFIG['filelist']['appendix'])

def build_lookup_table(src, dest):
    for key in src:
        for alias in src[key]:
            dest[alias] = key

def num(str):
    import ast

    try:
        return ast.literal_eval(str)
    except Exception:
        return None

def guess_os(fn):
    for os in CONFIG['tags']['oses']:
        if re.search('^%s-' % os , fn, re.I):
            return os
    return False

def guess_term(fn):
    """猜測可能的 term; 由於 RI 合約年數不由檔名決定，因此只回傳 od|l|m|h 或 False"""
    t = re.search('-(od|ri-(?:heavy|medium|light))$', fn)
    if t:
        if t.group(1) == 'od':
            return 'od'
        elif t.group(1) == 'ri-heavy':
            return 'h'
        elif t.group(1) == 'ri-medium':
            return 'm'
        elif t.group(1) == 'ri-light':
            return 'l'
    return False

def is_term_od(term):
    return term == 'od'

#
# Parse JSON from AWS
#

def parse_file(fn, tbl):
    """開啟並分析 fn，並將資料存至 tbl.
    由檔名猜測對應的 os 與 term."""
    
    c_os = guess_os(fn)
    c_term = guess_term(fn)

    if not (c_os and c_term):
        return
    
    with open(local_fn(fn), 'r') as fp:
        src = json.load(fp)

        # todo: Currency and Version check

        for src_regional in src['config']['regions']:
            c_region = lookup_dict(src_regional['region'], CONFIG['remap']['regions'])
            
            # todo: check region

            if not c_region in tbl.keys():
                tbl[c_region] = {}

            if not c_os in tbl[c_region].keys():
                tbl[c_region][c_os] = {}

            parse_instance_type(src_regional['instanceTypes'], c_term, tbl[c_region][c_os])

def fix_instance_size(c_instance, c_size):
    """Fix some possible typo
        cc1.8xlarge => cc2.8xlarge
        cc2.4xlarge => cg1.4xlarge
        Ref:
           - http://aws.amazon.com/ec2/instance-types/instance-details/
           - https://github.com/erans/ec2instancespricing/commit/71a24aaef1d2ceed2f3e4cefecc9b34b6d5f35b6
    """
    for typo in CONFIG['remap']['instance_size']:
        if typo['replace']['instance'] == c_instance and typo['replace']['size'] == c_size:
            return (typo['with']['instance'], typo['with']['size'])
            
    return (c_instance, c_size)

def parse_instance_type(src_its, c_term, tbl_its ):
    for src_it in src_its:
        c_instance = lookup_dict(src_it['type'], CONFIG['remap']['instances'])

        for src_sz in src_it['sizes']:
            c_size = lookup_dict(src_sz['size'], CONFIG['remap']['sizes'])

            (fixed_instance, fixed_size) = fix_instance_size(c_instance, c_size)
            
            if fixed_instance not in tbl_its.keys():
                tbl_its[fixed_instance] = {}
            
            if fixed_size not in tbl_its[fixed_instance].keys():
                tbl_its[fixed_instance][fixed_size] = {}

            if is_term_od(c_term):
                parse_od(src_sz, tbl_its[fixed_instance][fixed_size])
            else:
                parse_ri(src_sz, c_term, tbl_its[fixed_instance][fixed_size])

def parse_od(src_sz, tbl_sz):
    src_prices = src_sz['valueColumns'][0]['prices']
    tbl_sz['od'] = [num(src_prices['USD'])]

def parse_ri(src_sz, c_term, tbl_sz):
    src_vcs = src_sz['valueColumns']

    for vc in src_vcs :
        if vc['name'] == 'yrTerm1':
            upfront_1 = num(vc['prices']['USD'])
        elif vc['name'] == 'yrTerm3':
            upfront_3 = num(vc['prices']['USD'])
        elif vc['name'] == 'yrTerm1Hourly':
            hourly_1 = num(vc['prices']['USD'])
        elif vc['name'] == 'yrTerm3Hourly':
            hourly_3 = num(vc['prices']['USD'])

    if upfront_1 and hourly_1:
        tbl_sz['y1%s' % c_term] = [hourly_1, upfront_1]

    if upfront_3 and hourly_3:
        tbl_sz['y3%s' % c_term] = [hourly_3, upfront_3]


#
# Remove None (null) and N/A
#
def strip_nulls(obj):
    """除去 obj 及其子成員中，只包含 None 的 list, 以及不包含任何成員的 list 或 dict"""
    while strip_null_worker(obj) > 0:
        pass

    return obj

def strip_null_worker(obj):
    fired = 0
    tbd = []

    if type(obj) is list:
        for i in range(len(obj)-1):
            sub = obj[i]

            if (type(sub) is list and all(map(lambda x: x is None, sub))):
                tbd.append(i)
                fired = 1

            if (type(sub) is list or type(sub) is dict):
                if len(sub) == 0:
                    tbd.append(i)
                    fired = 1
                else:
                    fired = strip_null_worker(sub) or fired

        for i in sorted(tbd, reverse=True):
            del(obj[i])

    elif type(obj) is dict:
        for i in obj:
            sub = obj[i]

            if (type(sub) is list and all(map(lambda x: x is None, sub))):
                tbd.append(i)
                fired = 1

            if (type(sub) is list or type(sub) is dict):
                if len(sub) == 0:
                    tbd.append(i)
                    fired = 1
                else:
                    fired = strip_null_worker(sub) or fired
        for i in tbd:
            del(obj[i])

    return fired

#
# Procedural
#
def proc_args():
    from argparse import ArgumentParser

    tbl = CONFIG['lang']['help']

    parser = ArgumentParser(add_help=True, description=tbl['app'])
    
    parser.add_argument('--cleanup', '-c', help=tbl['cleanup'], action='store_const', const=True, default=False)
    parser.add_argument('--days-expire', '-d', help=tbl['days-expire'], default=7, type=int, metavar='DAYS')
    parser.add_argument('--force-fetch', '-f', help=tbl['force-fetch'], action="store_const", const=True, default=False)
    parser.add_argument('--indent', '-i', help=tbl['indent'], default=0, type=int, metavar='WIDTH')
    parser.add_argument('--output', '-o', help=tbl['output'], metavar='FILE')
    parser.add_argument('--pretty', '-p', help=tbl['pretty'], action='store_const', default=False, const=True)
    parser.add_argument('--tmp-dir', '-t', help=tbl['tmp-dir'], metavar='PATH')

    ARGS = parser.parse_args()
    if (ARGS.tmp_dir and os.path.isdir(ARGS.tmp_dir)):
        PATH['tmp'] = ARGS.tmp_dir

    return ARGS

def need_fetch():
    if ARGS.force_fetch:
        return True

    import time, datetime

    past = datetime.datetime.now() - datetime.timedelta(days=ARGS.days_expire)
    past = time.mktime(past.timetuple())
    
    for fn in CONFIG['filelist']['files']:
        fn = local_fn(fn)
        
        if not os.path.isfile(fn):
            return True

        if (os.path.getmtime(fn) < past):
            return True

    return False

def fetch():
    """Fetch data files from AWS"""
    if not need_fetch():
        return

    import urllib
    for fn in CONFIG['filelist']['files']:
        urllib.urlretrieve(aws_url(fn), local_fn(fn))

def convert():
    """Convert downloaded files"""
    output = {}
    fetch_list = CONFIG['filelist']['files']
    for fn in fetch_list:
        parse_file(fn, output)

    return strip_nulls(output)

def output(str):
    if ARGS.pretty and ARGS.indent == 0:
        ARGS.indent = 4

    if ARGS.indent:
        str = json.dumps(str, indent=ARGS.indent)
    else:
        str = json.dumps(str)

    if ARGS.output:
        with open(ARGS.output, "w") as fp:
            fp.write(str + "\n")
    else:
        print(str)

def cleanup():
    if not ARGS.cleanup:
        return

    for fn in CONFIG['filelist']['files']:
        fn = local_fn(fn)
        if os.path.isfile(fn):
            os.unlink(fn)

#
# Project Paths
#

PATH = {}

PATH['ROOT']    = os.path.realpath(__file__ + '/../..')
PATH['CONFIG']  = os.path.join(PATH['ROOT'], 'config')
PATH['TMP']     = os.path.join(PATH['ROOT'], 'tmp')

#
# Load Config Files
#

CONFIG = {'filelist': None, 'lang': None, 'remap': None, 'tags': None}

for fn in CONFIG:
    with open(os.path.join(PATH['CONFIG'], fn + '.json'), 'r') as fp:
        CONFIG[fn] = json.load(fp)

#
# Build Lookup Tables
#

for tbl_name in CONFIG['remap']['_lookup']:
    CONFIG['remap'][tbl_name] = {}
    build_lookup_table(CONFIG['remap']['_lookup'][tbl_name], CONFIG['remap'][tbl_name])

#
# Main
#

if __name__ == '__main__':
    ARGS = proc_args()
    fetch()
    output(convert())
    cleanup()
