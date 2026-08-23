import csv
import html
import json
import os
import sys
from pathlib import Path


def read_json(path):
    with open(path, 'r', encoding='utf-8') as f:
        return json.load(f)


def read_csv(path):
    if not os.path.exists(path):
        return []
    with open(path, 'r', encoding='utf-8-sig', newline='') as f:
        return list(csv.DictReader(f))


def js(value):
    return json.dumps(value, ensure_ascii=False)


def esc(value):
    return html.escape(str(value))


def metric(metrics, name, key):
    return metrics.get(name, {}).get('values', {}).get(key, 0)


def number(row, key):
    try:
        return float(row.get(key, 0) or 0)
    except (TypeError, ValueError):
        return 0.0


def build_peaks(rows):
    peaks = {}
    for row in rows:
        name = row.get('name', '')
        if not name:
            continue
        p = peaks.setdefault(name, {
            'cpu_peak_percent': 0.0, 'cpu_peak_at': '',
            'memory_peak_mb': 0.0, 'memory_peak_at': '',
            'memory_peak_percent': 0.0, 'memory_peak_percent_at': '',
            'net_rx_peak_mb': 0.0, 'net_rx_peak_at': '',
            'net_tx_peak_mb': 0.0, 'net_tx_peak_at': '',
            'pids_peak': 0, 'pids_peak_at': ''
        })
        ts = row.get('timestamp', '')
        cpu = number(row, 'cpu_percent')
        mem = number(row, 'memory_mb')
        mem_pct = number(row, 'memory_percent')
        rx = number(row, 'net_rx_mb')
        tx = number(row, 'net_tx_mb')
        try:
            pids = int(float(row.get('pids', 0) or 0))
        except (TypeError, ValueError):
            pids = 0
        if cpu > p['cpu_peak_percent']:
            p['cpu_peak_percent'], p['cpu_peak_at'] = cpu, ts
        if mem > p['memory_peak_mb']:
            p['memory_peak_mb'], p['memory_peak_at'] = mem, ts
        if mem_pct > p['memory_peak_percent']:
            p['memory_peak_percent'], p['memory_peak_percent_at'] = mem_pct, ts
        if rx > p['net_rx_peak_mb']:
            p['net_rx_peak_mb'], p['net_rx_peak_at'] = rx, ts
        if tx > p['net_tx_peak_mb']:
            p['net_tx_peak_mb'], p['net_tx_peak_at'] = tx, ts
        if pids > p['pids_peak']:
            p['pids_peak'], p['pids_peak_at'] = pids, ts
    return peaks


def main():
    if len(sys.argv) != 2:
        raise SystemExit('Usage: python generate-report.py <report-directory>')

    root = Path(sys.argv[1]).resolve()
    summary_path = root / 'summary.json'
    docker_path = root / 'docker-stats.csv'

    summary = read_json(summary_path)
    docker = read_csv(docker_path)
    metrics = summary.get('metrics', {})
    result = summary.get('result', {})
    test = summary.get('test', {})
    peaks = build_peaks(docker)

    (root / 'peak-resources.json').write_text(json.dumps(peaks, indent=2), encoding='utf-8')

    cpu, memory, net = [], [], []
    for row in docker:
        ts = row.get('timestamp', '')
        name = row.get('name', '')
        cpu.append({'t': ts, 'name': name, 'v': number(row, 'cpu_percent')})
        memory.append({'t': ts, 'name': name, 'v': number(row, 'memory_mb')})
        net.append({'t': ts, 'name': name, 'rx': number(row, 'net_rx_mb'), 'tx': number(row, 'net_tx_mb')})

    data = {'cpu': cpu, 'memory': memory, 'network': net}
    p95 = metric(metrics, 'login_duration', 'p(95)')
    p99 = metric(metrics, 'login_duration', 'p(99)')
    avg = metric(metrics, 'login_duration', 'avg')
    http_req_rate = metric(metrics, 'http_reqs', 'rate')
    duration = metric(metrics, 'http_req_duration', 'avg')

    peak_rows = ''.join(
        f'''<tr><td>{esc(name)}</td><td>{p['cpu_peak_percent']:.2f}%<br><span class="muted">{esc(p['cpu_peak_at'])}</span></td><td>{p['memory_peak_mb']:.2f} MB<br><span class="muted">{esc(p['memory_peak_at'])}</span></td><td>{p['memory_peak_percent']:.2f}%</td><td>{p['pids_peak']}</td></tr>'''
        for name, p in sorted(peaks.items())
    )

    report = f'''<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>E-UJIAN Load Test Report</title>
<style>
body{{font-family:Segoe UI,Arial,sans-serif;background:#f5f7fa;color:#172033;margin:0;padding:28px}}main{{max-width:1200px;margin:auto}}
h1{{margin:0 0 6px}}.muted{{color:#687386;font-size:12px}}.grid{{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;margin:22px 0}}
.card,.chart,.peak{{background:white;border:1px solid #e2e6ed;border-radius:10px;padding:16px;margin:16px 0}}.card b{{display:block;font-size:24px;margin-top:6px}}
svg{{width:100%;height:300px;display:block}}table{{width:100%;border-collapse:collapse;background:white}}th,td{{padding:9px;border-bottom:1px solid #e8ebf0;text-align:left}}@media(max-width:650px){{body{{padding:14px}}}}
</style></head><body><main>
<h1>E-UJIAN Load Test Report</h1><div class="muted">Generated: {esc(test.get('generated_at',''))}</div>
<div class="muted">Base URL: {esc(test.get('base_url',''))} · Accounts: {esc(test.get('accounts','001-709'))} · VUs: {esc(test.get('vus',709))} · Iterations: {esc(test.get('iterations',709))}</div>
<div class="grid"><div class="card">Attempts<b>{esc(result.get('attempts',0))}</b></div><div class="card">Success<b>{esc(result.get('success',0))}</b></div><div class="card">Failure<b>{esc(result.get('failure',0))}</b></div><div class="card">Success rate<b>{result.get('success_rate',0)*100:.2f}%</b></div><div class="card">Login avg<b>{avg:.0f} ms</b></div><div class="card">Login p95<b>{p95:.0f} ms</b></div><div class="card">Login p99<b>{p99:.0f} ms</b></div><div class="card">HTTP req/s<b>{http_req_rate:.2f}</b></div></div>
<div class="peak"><h2>Docker Resource Peaks</h2><table><tr><th>Container</th><th>CPU peak</th><th>Memory peak</th><th>Memory % peak</th><th>PIDs peak</th></tr>{peak_rows}</table></div>
<div class="chart"><h2>Docker CPU</h2><div id="cpuLegend" class="muted"></div><svg id="cpu"></svg></div>
<div class="chart"><h2>Docker memory</h2><div id="memLegend" class="muted"></div><svg id="memory"></svg></div>
<div class="chart"><h2>Docker network</h2><div class="muted">RX/TX are cumulative MB reported by Docker stats.</div><svg id="network"></svg></div>
<div class="chart"><h2>K6 metrics</h2><table><tr><th>Metric</th><th>Value</th></tr><tr><td>HTTP request average duration</td><td>{duration:.2f} ms</td></tr><tr><td>Login average</td><td>{avg:.2f} ms</td></tr><tr><td>Login p95</td><td>{p95:.2f} ms</td></tr><tr><td>Login p99</td><td>{p99:.2f} ms</td></tr><tr><td>HTTP request rate</td><td>{http_req_rate:.2f} req/s</td></tr></table></div>
</main><script>
const DATA={js(data)};
function chart(id,series,unit){{const svg=document.getElementById(id),W=1000,H=280,L=55,R=20,T=20,B=35;const all=series.flatMap(s=>s.points.map(p=>p.v)).filter(Number.isFinite);if(!all.length){{svg.innerHTML='<text x="20" y="40">No data collected.</text>';return;}}const max=Math.max(...all,1),min=Math.min(...all,0),xCount=Math.max(...series.map(s=>s.points.length),2);const x=i=>L+(W-L-R)*(i/(xCount-1)),y=v=>T+(H-T-B)*(1-(v-min)/(max-min||1));let out=`<line x1="${{L}}" y1="${{H-B}}" x2="${{W-R}}" y2="${{H-B}}" stroke="#bbb"/>`;series.forEach(s=>{{let d='';s.points.forEach((p,i)=>{{d+=(i?'L':'M')+x(i)+' '+y(p.v)+' ';}});out+=`<path d="${{d}}" fill="none" stroke="${{s.color}}" stroke-width="2"/>`;}});out+=`<text x="8" y="${{T+8}}" font-size="12">${{max.toFixed(1)}} ${{unit}}</text><text x="8" y="${{H-B}}" font-size="12">${{min.toFixed(1)}} ${{unit}}</text>`;svg.setAttribute('viewBox',`0 0 ${{W}} ${{H}}`);svg.innerHTML=out;}}
function grouped(rows,value){{const m={{}};rows.forEach(r=>{{(m[r.name]??=[]).push({{v:Number(r[value]),t:r.t}})}});return Object.entries(m).map(([name,points],i)=>({{name,points,color:['#2563eb','#dc2626','#16a34a','#9333ea','#ea580c','#0891b2'][i%6]}}));}}
const cpuSeries=grouped(DATA.cpu,'v'),memSeries=grouped(DATA.memory,'v');chart('cpu',cpuSeries,'%');chart('memory',memSeries,'MB');const netSeries=[],names=[...new Set(DATA.network.map(x=>x.name))];names.forEach((name,i)=>{{const rows=DATA.network.filter(x=>x.name===name);netSeries.push({{name:name+' RX',points:rows.map(x=>({{v:x.rx}})),color:'#2563eb'}});netSeries.push({{name:name+' TX',points:rows.map(x=>({{v:x.tx}})),color:'#dc2626'}});}});chart('network',netSeries,'MB');document.getElementById('cpuLegend').textContent=cpuSeries.map(s=>s.name).join(' · ');document.getElementById('memLegend').textContent=memSeries.map(s=>s.name).join(' · ');
</script></body></html>'''
    out = root / 'summary.html'
    out.write_text(report, encoding='utf-8')
    print(out)


if __name__ == '__main__':
    main()
