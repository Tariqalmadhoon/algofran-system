import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../app/providers.dart';
import '../../../core/notifications/daily_reminder_service.dart';

class ReminderSettingsScreen extends ConsumerStatefulWidget {
  const ReminderSettingsScreen({super.key});

  @override
  ConsumerState<ReminderSettingsScreen> createState() =>
      _ReminderSettingsScreenState();
}

class _ReminderSettingsScreenState
    extends ConsumerState<ReminderSettingsScreen> {
  bool _loading = true;
  bool _saving = false;
  bool _enabled = true;
  TimeOfDay _maghrib = const TimeOfDay(hour: 18, minute: 0);
  TimeOfDay _isha = const TimeOfDay(hour: 20, minute: 30);

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    final values = await ref.read(dailyReminderServiceProvider).preferences();
    if (!mounted) return;
    setState(() {
      _enabled = values.enabled;
      _maghrib = values.maghribTime;
      _isha = values.ishaTime;
      _loading = false;
    });
  }

  Future<void> _pick(bool maghrib) async {
    final value = await showTimePicker(
      context: context,
      initialTime: maghrib ? _maghrib : _isha,
      helpText: maghrib ? 'موعد تذكير المغرب' : 'موعد تذكير ما بعد العشاء',
    );
    if (value == null || !mounted) return;
    setState(() => maghrib ? _maghrib = value : _isha = value);
  }

  Future<void> _save() async {
    setState(() => _saving = true);
    final service = ref.read(dailyReminderServiceProvider);
    if (_enabled) await service.requestPermission();
    await service.savePreferences(
      ReminderPreferences(
        enabled: _enabled,
        maghribMinutes: _maghrib.hour * 60 + _maghrib.minute,
        ishaMinutes: _isha.hour * 60 + _isha.minute,
      ),
    );
    await service.refreshSchedule();
    if (!mounted) return;
    setState(() => _saving = false);
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text('تم حفظ مواعيد التذكير على هذا الجهاز.'),
        behavior: SnackBarBehavior.floating,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('تذكيرات السجل اليومي')),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : ListView(
              padding: const EdgeInsets.all(18),
              children: [
                Card(
                  child: SwitchListTile.adaptive(
                    value: _enabled,
                    onChanged: (value) => setState(() => _enabled = value),
                    title: const Text(
                      'تفعيل التذكير اليومي',
                      style: TextStyle(fontWeight: FontWeight.w800),
                    ),
                    subtitle: const Text(
                      'يعمل محليًا حتى دون إنترنت، ويمكن تغيير الموعدين في أي وقت.',
                    ),
                    secondary: const Icon(Icons.notifications_active_rounded),
                  ),
                ),
                const SizedBox(height: 12),
                Card(
                  child: Column(
                    children: [
                      ListTile(
                        enabled: _enabled,
                        leading: const Icon(Icons.wb_twilight_rounded),
                        title: const Text('تذكير وقت المغرب'),
                        subtitle: const Text(
                          'التذكير الأساسي لبدء أو مراجعة التسجيل',
                        ),
                        trailing: Text(
                          _maghrib.format(context),
                          textDirection: TextDirection.ltr,
                          style: const TextStyle(
                            fontWeight: FontWeight.w800,
                            fontSize: 16,
                          ),
                        ),
                        onTap: _enabled ? () => _pick(true) : null,
                      ),
                      const Divider(height: 1),
                      ListTile(
                        enabled: _enabled,
                        leading: const Icon(Icons.nightlight_round),
                        title: const Text('تذكير ما بعد العشاء'),
                        subtitle: const Text(
                          'تذكير ثانٍ إذا بقي السجل غير مكتمل',
                        ),
                        trailing: Text(
                          _isha.format(context),
                          textDirection: TextDirection.ltr,
                          style: const TextStyle(
                            fontWeight: FontWeight.w800,
                            fontSize: 16,
                          ),
                        ),
                        onTap: _enabled ? () => _pick(false) : null,
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 18),
                FilledButton.icon(
                  onPressed: _saving ? null : _save,
                  icon: _saving
                      ? const SizedBox.square(
                          dimension: 18,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        )
                      : const Icon(Icons.save_rounded),
                  label: const Text('حفظ وضبط التذكيرات'),
                ),
              ],
            ),
    );
  }
}
