// mobile/lib/screens/notifications_screen.dart
import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../theme/app_theme.dart';

class NotificationsScreen extends StatefulWidget {
  const NotificationsScreen({super.key});

  @override
  State<NotificationsScreen> createState() => _NotificationsScreenState();
}

class _NotificationsScreenState extends State<NotificationsScreen> {
  List<dynamic> _notifications = [];
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _fetchNotifications();
  }

  void _fetchNotifications() async {
    try {
      final response = await ApiService.get('notifications.php');
      if (response['status'] == 200) {
        setState(() {
          _notifications = response['data'];
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  void _markAsRead(int id) async {
    if (id == 0) return; // Örnek bildirim için işlem yapma
    await ApiService.post('notifications.php', {
      'id': id,
      '_method': 'PATCH', // API Helper handle method override might be needed, or use PATCH
    });
    // Visual update
    setState(() {
      final index = _notifications.indexWhere((n) => n['id'] == id);
      if (index != -1) _notifications[index]['is_read'] = 1;
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.transparent,
      appBar: AppBar(
        title: const Text('BİLDİRİMLER'),
        backgroundColor: Colors.transparent,
        elevation: 0,
        centerTitle: true,
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: AppTheme.primaryColor))
          : ListView.builder(
              padding: const EdgeInsets.all(20),
              itemCount: _notifications.length,
              itemBuilder: (context, index) {
                final item = _notifications[index];
                return _buildNotificationItem(item);
              },
            ),
    );
  }

  Widget _buildNotificationItem(dynamic item) {
    final type = item['type'] ?? 'info';
    final isRead = (item['is_read'] == 1 || item['is_read'] == true);
    
    IconData icon;
    Color color;

    switch (type) {
      case 'success':
        icon = Icons.check_circle_outline;
        color = Colors.greenAccent;
        break;
      case 'warning':
        icon = Icons.warning_amber_outlined;
        color = Colors.orangeAccent;
        break;
      case 'error':
        icon = Icons.error_outline;
        color = Colors.redAccent;
        break;
      default:
        icon = Icons.info_outline;
        color = AppTheme.primaryColor;
    }

    return GestureDetector(
      onTap: () => _markAsRead(item['id']),
      child: Opacity(
        opacity: isRead ? 0.6 : 1.0,
        child: Container(
          margin: const EdgeInsets.only(bottom: 16),
          padding: const EdgeInsets.all(20),
          decoration: AppTheme.glassDecoration,
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: color.withOpacity(0.1),
                  shape: BoxShape.circle,
                ),
                child: Icon(icon, color: color, size: 24),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(
                          item['title'] ?? 'Bildirim',
                          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                        ),
                        if (!isRead)
                          Container(
                            width: 8, height: 8,
                            decoration: const BoxDecoration(color: AppTheme.primaryColor, shape: BoxShape.circle),
                          ),
                      ],
                    ),
                    const SizedBox(height: 6),
                    Text(
                      item['message'] ?? '',
                      style: TextStyle(color: Colors.white.withOpacity(0.6), fontSize: 13, height: 1.4),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
